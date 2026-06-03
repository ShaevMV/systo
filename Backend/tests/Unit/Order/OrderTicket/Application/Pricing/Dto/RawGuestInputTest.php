<?php

declare(strict_types=1);

namespace Tests\Unit\Order\OrderTicket\Application\Pricing\Dto;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tickets\Order\OrderTicket\Application\Pricing\Dto\RawGuestInput;
use Tickets\Order\OrderTicket\Application\Pricing\Dto\RawGuestOptionInput;

class RawGuestInputTest extends TestCase
{
    private const TICKET_TYPE_ID = 'b2222222-2222-2222-2222-222222222222';
    private const OPTION_ID = 'a1111111-1111-1111-1111-111111111111';

    public function test_from_state_creates_minimal_guest(): void
    {
        $input = RawGuestInput::fromState([
            'value' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'ticket_type_id' => self::TICKET_TYPE_ID,
        ]);

        self::assertSame('Иван Иванов', $input->value);
        self::assertSame('ivan@example.com', $input->email);
        self::assertSame(self::TICKET_TYPE_ID, $input->ticketTypeId->value());
        self::assertSame([], $input->options);
        self::assertNull($input->promoCode);
    }

    public function test_from_state_creates_full_guest_with_options_and_promocode(): void
    {
        $input = RawGuestInput::fromState([
            'value' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'ticket_type_id' => self::TICKET_TYPE_ID,
            'options' => [
                ['option_id' => self::OPTION_ID, 'qty' => 2],
            ],
            'promo_code' => 'FRIEND10',
        ]);

        self::assertSame('FRIEND10', $input->promoCode);
        self::assertCount(1, $input->options);
        self::assertInstanceOf(RawGuestOptionInput::class, $input->options[0]);
        self::assertSame(2, $input->options[0]->qty);
    }

    public function test_from_state_rejects_missing_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('value');

        RawGuestInput::fromState([
            'email' => 'ivan@example.com',
            'ticket_type_id' => self::TICKET_TYPE_ID,
        ]);
    }

    public function test_from_state_rejects_empty_value(): void
    {
        // ФИО обязательно — нельзя оформить билет на «пустого» гостя
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('value');

        RawGuestInput::fromState([
            'value' => '',
            'email' => 'ivan@example.com',
            'ticket_type_id' => self::TICKET_TYPE_ID,
        ]);
    }

    public function test_from_state_rejects_missing_email(): void
    {
        // email обязателен для каждого гостя (включая водителя парковки) — на него уходит анкета.
        // Решение пользователя 2026-06-03 — см. PHPDoc RawGuestInput.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('email');

        RawGuestInput::fromState([
            'value' => 'Иван Иванов',
            'ticket_type_id' => self::TICKET_TYPE_ID,
        ]);
    }

    public function test_from_state_rejects_empty_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('email');

        RawGuestInput::fromState([
            'value' => 'Иван Иванов',
            'email' => '',
            'ticket_type_id' => self::TICKET_TYPE_ID,
        ]);
    }

    public function test_from_state_rejects_null_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('email');

        RawGuestInput::fromState([
            'value' => 'Иван Иванов',
            'email' => null,
            'ticket_type_id' => self::TICKET_TYPE_ID,
        ]);
    }

    public function test_from_state_rejects_missing_ticket_type_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ticket_type_id');

        RawGuestInput::fromState([
            'value' => 'Иван Иванов',
            'email' => 'ivan@example.com',
        ]);
    }

    public function test_from_state_rejects_email_without_at_sign(): void
    {
        // filter_var(FILTER_VALIDATE_EMAIL) — фронт мог пропустить валидацию
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('email');

        RawGuestInput::fromState([
            'value' => 'Иван Иванов',
            'email' => 'not-an-email',
            'ticket_type_id' => self::TICKET_TYPE_ID,
        ]);
    }

    public function test_from_state_rejects_email_with_spaces(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('email');

        RawGuestInput::fromState([
            'value' => 'Иван Иванов',
            'email' => 'ivan @example.com',
            'ticket_type_id' => self::TICKET_TYPE_ID,
        ]);
    }

    public function test_from_state_accepts_unicode_email(): void
    {
        // RFC 6531 — кириллический local-part. filter_var() для UNICODE-домена допускает.
        // Это тест на то, что мы НЕ слишком жадно отвергаем валидные email.
        $input = RawGuestInput::fromState([
            'value' => 'Иван Иванов',
            'email' => 'ivan+festival@example.com',
            'ticket_type_id' => self::TICKET_TYPE_ID,
        ]);

        self::assertSame('ivan+festival@example.com', $input->email);
    }

    public function test_from_state_normalizes_empty_promo_code_to_null(): void
    {
        $input = RawGuestInput::fromState([
            'value' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'ticket_type_id' => self::TICKET_TYPE_ID,
            'promo_code' => '',
        ]);

        self::assertNull($input->promoCode);
    }

    public function test_from_state_trims_promo_code_whitespace(): void
    {
        // пользователи копируют промокод с пробелами по краям из email/чатов
        $input = RawGuestInput::fromState([
            'value' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'ticket_type_id' => self::TICKET_TYPE_ID,
            'promo_code' => '  FRIEND10  ',
        ]);

        self::assertSame('FRIEND10', $input->promoCode);
    }

    public function test_from_state_treats_whitespace_only_promo_code_as_null(): void
    {
        $input = RawGuestInput::fromState([
            'value' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'ticket_type_id' => self::TICKET_TYPE_ID,
            'promo_code' => '   ',
        ]);

        self::assertNull($input->promoCode);
    }

    public function test_from_state_rejects_non_array_options(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('options');

        RawGuestInput::fromState([
            'value' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'ticket_type_id' => self::TICKET_TYPE_ID,
            'options' => 'not-an-array',
        ]);
    }

    public function test_from_state_expands_multiple_options(): void
    {
        $input = RawGuestInput::fromState([
            'value' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'ticket_type_id' => self::TICKET_TYPE_ID,
            'options' => [
                ['option_id' => self::OPTION_ID, 'qty' => 1],
                ['option_id' => 'a1111111-1111-1111-1111-111111111112', 'qty' => 3],
            ],
        ]);

        self::assertCount(2, $input->options);
        self::assertSame(1, $input->options[0]->qty);
        self::assertSame(3, $input->options[1]->qty);
    }
}
