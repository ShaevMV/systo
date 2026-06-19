<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Domain;

/**
 * Канонический справочник событий писем: код события → дефолтный slug шаблона + метка.
 *
 * Единственный источник правды «какое письмо за каким событием». Используется:
 *  - привязками шаблонов (TemplateBinding) — ось `event` резолва (фаза 1);
 *  - диспетчером отправки (MailDispatcher) — выбор slug, когда привязки нет (фаза 2).
 *
 * defaultSlug() = текущий зашитый в соответствующий Mailable slug → полная обратная
 * совместимость: нет привязки под событие → defaultSlug() → прежний рендер (Mustache/blade).
 */
final class EmailEvent
{
    // Заказные / листовые события
    public const ORDER_CREATED = 'order_created';
    public const ORDER_PAID = 'order_paid';
    public const ORDER_PAID_FRIENDLY = 'order_paid_friendly';
    public const ORDER_PAID_LIVE = 'order_paid_live';
    public const ORDER_CANCEL = 'order_cancel';
    public const ORDER_CHANGED = 'order_changed';
    public const ORDER_DIFFICULTIES = 'order_difficulties';
    public const ORDER_LIVE_ISSUED = 'order_live_issued';
    public const LIST_APPROVED = 'list_approved';
    public const LIST_CANCEL = 'list_cancel';
    public const LIST_DIFFICULTIES = 'list_difficulties';

    // Аккаунт / анкеты
    public const USER_REGISTERED = 'user_registered';
    public const PASSWORD_RESET = 'password_reset';
    public const INVITE = 'invite';
    public const QUESTIONNAIRE = 'questionnaire';
    public const QUESTIONNAIRE_APPROVED = 'questionnaire_approved';

    /** event → дефолтный slug шаблона (= текущий зашитый в Mailable). */
    private const DEFAULT_SLUG = [
        self::ORDER_CREATED => 'orderToCreate',
        self::ORDER_PAID => 'orderToPaid',
        self::ORDER_PAID_FRIENDLY => 'TypeTicketMailOrderToPaidFriendly1',
        self::ORDER_PAID_LIVE => 'orderToPaidLiveTicket',
        self::ORDER_CANCEL => 'orderToCancel',
        self::ORDER_CHANGED => 'orderToChangeTicket',
        self::ORDER_DIFFICULTIES => 'orderToDifficultiesArose',
        self::ORDER_LIVE_ISSUED => 'orderToLiveTicketIssued',
        self::LIST_APPROVED => 'orderListApproved',
        self::LIST_CANCEL => 'orderListCancel',
        self::LIST_DIFFICULTIES => 'orderListDifficultiesArose',
        self::USER_REGISTERED => 'newUser',
        self::PASSWORD_RESET => 'passwordResets',
        self::INVITE => 'invate',
        self::QUESTIONNAIRE => 'questionnaire',
        self::QUESTIONNAIRE_APPROVED => 'questionnaireApproved',
    ];

    /** event → человекочитаемая метка (для селектора события в админке). */
    private const LABEL = [
        self::ORDER_CREATED => 'Заказ создан',
        self::ORDER_PAID => 'Заказ оплачен',
        self::ORDER_PAID_FRIENDLY => 'Заказ оплачен (Friendly)',
        self::ORDER_PAID_LIVE => 'Живой билет оплачен',
        self::ORDER_CANCEL => 'Заказ отменён',
        self::ORDER_CHANGED => 'Данные заказа изменены',
        self::ORDER_DIFFICULTIES => 'Трудности с заказом',
        self::ORDER_LIVE_ISSUED => 'Живой билет выдан',
        self::LIST_APPROVED => 'Список одобрен',
        self::LIST_CANCEL => 'Список отменён',
        self::LIST_DIFFICULTIES => 'Трудности со списком',
        self::USER_REGISTERED => 'Регистрация пользователя',
        self::PASSWORD_RESET => 'Сброс пароля',
        self::INVITE => 'Приглашение',
        self::QUESTIONNAIRE => 'Анкета гостя',
        self::QUESTIONNAIRE_APPROVED => 'Анкета одобрена',
    ];

    /** @return string[] все коды событий */
    public static function all(): array
    {
        return array_keys(self::DEFAULT_SLUG);
    }

    public static function isValid(string $event): bool
    {
        return isset(self::DEFAULT_SLUG[$event]);
    }

    /** Дефолтный slug события или null, если событие неизвестно. */
    public static function defaultSlug(string $event): ?string
    {
        return self::DEFAULT_SLUG[$event] ?? null;
    }

    /** @return array<int, array{value: string, label: string}> каталог для селектора в админке */
    public static function catalog(): array
    {
        return array_map(
            static fn (string $value): array => ['value' => $value, 'label' => self::LABEL[$value]],
            self::all(),
        );
    }
}
