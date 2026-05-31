<?php

declare(strict_types=1);

namespace Tests\Unit\Option\Dto;

use PHPUnit\Framework\TestCase;
use Tickets\Option\Dto\OptionTicketTypeBindingDto;

/**
 * Unit-тест для `OptionTicketTypeBindingDto::fromState()`.
 *
 * Привязка опции к типу билета с описанием специфичным для этого
 * типа билета (хранится на pivot `option_ticket_type.description`).
 */
class OptionTicketTypeBindingDtoTest extends TestCase
{
    /** @test */
    public function it_builds_binding_with_description(): void
    {
        $binding = OptionTicketTypeBindingDto::fromState([
            'ticket_type_id' => '11111111-1111-1111-1111-111111111111',
            'description' => 'Для соорганизатора саженец идёт в подарок',
        ]);

        $this->assertSame(
            '11111111-1111-1111-1111-111111111111',
            $binding->getTicketTypeId()->value()
        );
        $this->assertSame(
            'Для соорганизатора саженец идёт в подарок',
            $binding->getDescription()
        );
    }

    /** @test */
    public function it_allows_null_description(): void
    {
        $binding = OptionTicketTypeBindingDto::fromState([
            'ticket_type_id' => '11111111-1111-1111-1111-111111111111',
        ]);

        $this->assertNull($binding->getDescription());
    }
}
