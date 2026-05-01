<?php

declare(strict_types=1);

namespace Tickets\Orders\Shared\Domain;

use DomainException;
use Shared\Domain\Aggregate\AggregateRoot;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\History\Domain\Event\OrderStatusChangedEvent;
use Tickets\History\Trait\HasHistory;
use Tickets\Orders\Shared\Contract\OrderAccessPolicyInterface;
use Tickets\Orders\Shared\Contract\OrderPipelineInterface;
use Tickets\Orders\Shared\Contract\OrderSpecificationInterface;
use Tickets\Orders\Shared\Domain\ValueObject\OrderType;

/**
 * Базовый агрегат заказа.
 *
 * Содержит общую инфраструктуру для всех типов заказов:
 * - Domain Events (через AggregateRoot::record)
 * - История изменений (через HasHistory::recordHistory)
 * - Валидация переходов статусов (через OrderPipeline)
 * - Проверка прав доступа (через OrderAccessPolicy)
 * - Проверка бизнес-правил (через OrderSpecification)
 *
 * Оплата и стоимость билетов — НЕ часть BaseOrder.
 * Они специфичны только для платных типов заказов (Guest, Live).
 *
 * Каждый конкретный тип заказа:
 * 1. Определяет свои дополнительные поля
 * 2. Реализует фабричные методы (create, и методы смены статуса)
 * 3. Предоставляет Pipeline, Specification и AccessPolicy через фабричные методы
 *
 * Источник: Роберт Мартин — «Чистая архитектура», глава «Зависимости» (Dependency Rule)
 */
abstract class BaseOrder extends AggregateRoot
{
    use HasHistory;

    /** UUID детского типа билета — исключает рассылку анкет всем типам заказов. */
    public const CHILD_TICKET_TYPE_ID = 'c3d4e5f6-a7b8-9012-cdef-345678901235';

    protected Uuid   $id;
    protected Uuid   $festivalId;  // Все заказы привязаны к фестивалю
    protected Uuid   $userId;      // Владелец заказа (для дружеских — пушер)
    protected Status $status;
    protected array  $tickets;     // GuestsDto[]

    // ----------------------------------------------------------------
    // Абстрактные методы — каждый тип заказа определяет свою реализацию
    // ----------------------------------------------------------------

    abstract public function getOrderType(): OrderType;

    abstract protected function createPipeline(): OrderPipelineInterface;

    abstract protected function createSpecification(): OrderSpecificationInterface;

    abstract protected function createAccessPolicy(): OrderAccessPolicyInterface;

    /**
     * Реакция агрегата на смену статуса: запись Domain Events.
     *
     * Вызывается из changeStatus() после проверки Pipeline и обновления поля status.
     * Здесь же бросать исключения если для перехода нужны обязательные параметры.
     */
    abstract protected function onStatusChanged(Status $from, Status $to, array $params): void;

    // ----------------------------------------------------------------
    // Финальная логика смены статуса
    // ----------------------------------------------------------------

    /**
     * Меняет статус заказа через Pipeline-валидацию.
     *
     * Защищён (protected) — конкретные типы заказов вызывают его из своих
     * семантических методов (confirmPayment, cancelOrder, и т.д.).
     * Это обеспечивает читаемый публичный API и единое место валидации.
     */
    final protected function changeStatus(Status $newStatus, array $params = []): void
    {
        $pipeline = $this->createPipeline();

        if (!$pipeline->canTransition($this, $newStatus)) {
            throw new DomainException(
                sprintf(
                    'Переход из статуса «%s» в «%s» не разрешён для типа заказа «%s»',
                    $this->status->getHumanStatus(),
                    $newStatus->getHumanStatus(),
                    $this->getOrderType()->value(),
                )
            );
        }

        $oldStatus    = $this->status;
        $this->status = $newStatus;

        $this->onStatusChanged($oldStatus, $newStatus, $params);

        $this->recordHistory(new OrderStatusChangedEvent(
            fromStatus: (string)$oldStatus,
            toStatus:   (string)$newStatus,
            comment:    $params['comment'] ?? null,
        ));
    }

    // ----------------------------------------------------------------
    // Финальные публичные методы — доступны всем потребителям
    // ----------------------------------------------------------------

    /**
     * Универсальный метод смены статуса для использования через Facade/CommandHandler.
     *
     * Конкретные типы заказов также предоставляют семантические методы
     * (toPaid, toCancel и т.д.) для прямого использования в Application слое.
     */
    final public function processStatusChange(Status $newStatus, array $params = []): void
    {
        $this->changeStatus($newStatus, $params);
    }

    /** Возвращает список допустимых следующих статусов: ['status_name' => 'Человеческое название']. */
    final public function getAvailableTransitions(): array
    {
        return $this->createPipeline()->getAvailableTransitions($this);
    }

    /** Проверяет право доступа пользователя к созданию заказа этого типа. */
    final public function canCreate(string $role): bool
    {
        return $this->createAccessPolicy()->canCreate($role);
    }

    /** Проверяет право доступа пользователя на просмотр списка заказов. */
    final public function canViewList(string $role): bool
    {
        return $this->createAccessPolicy()->canViewList($role);
    }

    /** Проверяет право доступа пользователя на просмотр конкретного заказа. */
    final public function canViewItem(string $role, ?Uuid $currentUserId = null): bool
    {
        return $this->createAccessPolicy()->canViewItem($role, $this, $currentUserId);
    }

    /** Проверяет право на смену статуса. */
    final public function canChangeStatus(string $role, Status $newStatus): bool
    {
        return $this->createAccessPolicy()->canChangeStatus($role, $newStatus);
    }

    // ----------------------------------------------------------------
    // Геттеры
    // ----------------------------------------------------------------

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getTickets(): array
    {
        return $this->tickets;
    }

    public function getFestivalId(): Uuid
    {
        return $this->festivalId;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }
}
