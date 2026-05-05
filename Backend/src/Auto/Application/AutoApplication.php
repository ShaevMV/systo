<?php

declare(strict_types=1);

namespace Tickets\Auto\Application;

use DomainException;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Auto\Dto\AutoDto;
use Tickets\Auto\Repositories\AutoRepositoryInterface;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\User\Account\Repositories\UserRepositoriesInterface;

/**
 * Сервис для управления авто, привязанными к заказу-списку.
 *
 * Доступ управляется на уровне контроллера; здесь — только бизнес-логика.
 * Все изменения дублируются в таблицу `auto` базы Baza.
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
     * Добавить авто к заказу-списку. Возвращает созданный AutoDto.
     */
    public function add(Uuid $orderId, string $number): AutoDto
    {
        $number = trim($number);
        if ($number === '') {
            throw new DomainException('Номер авто не может быть пустым');
        }

        $order = $this->loadListOrder($orderId);

        // Денормализуем project/curator в саму запись авто — фиксируем значения
        // на момент добавления, чтобы потом не зависеть от изменений в заказе.
        $project = (string) ($order->getProject() ?? '');
        $curator = $this->buildCurator($order);

        $auto = AutoDto::create($orderId, $number, $project, $curator);
        $this->autoRepository->create($auto);

        $this->autoRepository->setInBazaAuto(
            $auto,
            $curator,
            $project,
            $order->getFestivalId(),
        );

        return $auto;
    }

    /**
     * Создать сразу пачку авто (используется при createList).
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
     */
    public function remove(Uuid $orderId, Uuid $autoId): void
    {
        $auto = $this->autoRepository->getById($autoId);
        if ($auto === null || !$auto->orderTicketId->equals($orderId)) {
            throw new DomainException('Авто не найдено в заказе');
        }

        $order = $this->loadListOrder($orderId);

        $this->autoRepository->delete($autoId);

        // Используем сохранённые в DTO project/curator (зафиксированы на момент создания),
        // чтобы найти точно ту же запись в Baza, которую туда вставляли.
        $this->autoRepository->removeFromBazaAuto(
            $auto,
            (string) ($auto->curator ?? $this->buildCurator($order)),
            (string) ($auto->project ?? ($order->getProject() ?? '')),
            $order->getFestivalId(),
        );
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
