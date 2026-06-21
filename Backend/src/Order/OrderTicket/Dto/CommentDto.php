<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Dto;

use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\ValueObject\CommentSource;

/**
 * DTO одной записи треда комментариев к заказу.
 *
 * Поля соответствуют колонкам таблицы `comment`. Имена свойств совпадают с колонками,
 * чтобы `toArray()` отдавал готовый набор для Eloquent `create()` (единый формат данных).
 *
 *  - $id             — id записи (задаётся явно → известна созданная строка треда).
 *  - $user_id        — автор-org (admin/manager). Для baza/qr/system = null.
 *  - $author_name    — отображаемое имя автора (имя org-юзера / ФИО персонала Baza / «qr»).
 *  - $author_source  — источник записи (см. CommentSource).
 *  - $is_checkin     — сохранённая семантика существующего поля.
 */
final class CommentDto extends AbstractionEntity
{
    public function __construct(
        protected Uuid $id,
        protected ?Uuid $user_id,
        protected Uuid $order_tickets_id,
        protected string $comment,
        protected ?string $author_name = null,
        protected string $author_source = CommentSource::ORG_USER,
        protected bool $is_checkin = false,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public static function fromState(array $data): self
    {
        return new self(
            isset($data['id']) ? new Uuid($data['id']) : Uuid::random(),
            isset($data['user_id']) && $data['user_id'] !== null ? new Uuid($data['user_id']) : null,
            new Uuid($data['order_id'] ?? $data['order_tickets_id']),
            $data['comment'],
            $data['author_name'] ?? null,
            $data['author_source'] ?? CommentSource::ORG_USER,
            (bool)($data['is_checkin'] ?? false),
        );
    }
}
