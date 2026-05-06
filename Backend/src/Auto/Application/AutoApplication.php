<?php

declare(strict_types=1);

namespace Tickets\Auto\Application;

use DomainException;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Auto\Dto\AutoDto;
use Tickets\Auto\Repositories\AutoRepositoryInterface;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\User\Account\Repositories\UserRepositoriesInterface;

/**
 * Сервис для управления авто заказа-списка.
 *
 * Правило синхронизации с Baza:
 * - add() / addMany() — только локальная БД. Baza НЕ трогается.
 * - pushAllToBasaByOrder() — вызывается при APPROVE_LIST: все авто заказа → Baza.
 * - removeAllFromBasaByOrder() — вызывается при CANCEL_LIST / DIFFICULTIES_AROSE_LIST: удалить из Baza по order_id.
 */
final class AutoApplication
{
    public function __construct(
        private AutoRepositoryInterface        $autoRepository,
        private OrderTicketRepositoryInterface $orderRepository,
        private UserRepositoriesInterface      $userRepository,
    ) {
    }

    /**
     * Добавить авто к заказу-списку.
     * Если заказ уже одобрен (APPROVE_LIST) — авто сразу пишется в Baza.
     */
    public function add(Uuid $orderId, string $number): AutoDto
    {
        $number = trim($number);
        if ($number === '') {
            throw new DomainException('Номер авто не может быть пустым');
        }

        $order   = $this->loadListOrder($orderId);
        $project = (string) ($order->getProject() ?? '');
        $curator = $this->buildCurator($order);

        $auto = AutoDto::create($orderId, $number, $project, $curator);
        $this->autoRepository->create($auto);

        if ($order->getStatus()->equals(new Status(Status::APPROVE_LIST))) {
            $this->autoRepository->setInBazaAuto($auto, $order->getFestivalId());
        }

        return $auto;
    }

    /**
     * Создать пачку авто (используется при createList).
     *
     * @param string[] $numbers
     * @return AutoDto[]
     */
    public function addMany(Uuid $orderId, array $numbers): array
    {
        $result = [];
        foreach ($numbers as $number) {
            if (trim((string) $number) === '') {
                continue;
            }
            $result[] = $this->add($orderId, (string) $number);
        }
        return $result;
    }

    /**
     * Удалить авто из заказа.
     * Если заказ одобрен (APPROVE_LIST) — пересинхронизируем Baza:
     * удаляем все записи этого заказа и вставляем оставшиеся авто заново.
     */
    public function remove(Uuid $orderId, Uuid $autoId): void
    {
        $auto = $this->autoRepository->getById($autoId);
        if ($auto === null || !$auto->orderTicketId->equals($orderId)) {
            throw new DomainException('Авто не найдено в заказе');
        }

        $order = $this->loadListOrder($orderId);

        $this->autoRepository->delete($autoId);

        if ($order->getStatus()->equals(new Status(Status::APPROVE_LIST))) {
            // Baza не поддерживает удаление по UUID авто — делаем полную пересинхронизацию
            $this->pushAllToBasaByOrder($orderId);
        }
    }

    /**
     * Получить список авто заказа.
     *
     * @return AutoDto[]
     */
    public function getByOrder(Uuid $orderId): array
    {
        return $this->autoRepository->getByOrderId($orderId);
    }

    /**
     * Синхронизировать все авто заказа в Baza. Вызывать при APPROVE_LIST.
     * Сначала удаляет старые (повторный approve после difficulties), затем вставляет актуальные.
     */
    public function pushAllToBasaByOrder(Uuid $orderId): void
    {
        $order = $this->loadListOrder($orderId);

        // Очищаем старые записи этого заказа в Baza (идемпотентность при повторном approve).
        $this->autoRepository->removeAllFromBazaByOrderId($orderId);

        foreach ($this->autoRepository->getByOrderId($orderId) as $auto) {
            $this->autoRepository->setInBazaAuto($auto, $order->getFestivalId());
        }
    }

    /**
     * Удалить все авто заказа из Baza. Вызывать при CANCEL_LIST / DIFFICULTIES_AROSE_LIST.
     */
    public function removeAllFromBasaByOrder(Uuid $orderId): void
    {
        $this->autoRepository->removeAllFromBazaByOrderId($orderId);
    }

    private function loadListOrder(Uuid $orderId): OrderTicketDto
    {
        $order = $this->orderRepository->findOrder($orderId);
        if ($order === null) {
            throw new DomainException('Заказ не найден: ' . $orderId->value());
        }
        if ($order->getCuratorId() === null) {
            throw new DomainException('Авто можно добавлять только в заказ-список');
        }
        return $order;
    }

    private function buildCurator(OrderTicketDto $order): string
    {
        $curatorId = $order->getCuratorId();
        if ($curatorId === null) {
            return '';
        }

        $user = $this->userRepository->findAccountById($curatorId);
        if ($user === null) {
            return $curatorId->value();
        }

        return trim(($user->email ?? '') . ' | ' . ($user->name ?? ''));
    }
}
