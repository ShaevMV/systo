<?php

declare(strict_types=1);

namespace Tests\Unit\Option\Dto;

use PHPUnit\Framework\TestCase;
use Tickets\Option\Dto\OptionForTicketTypeView;

/**
 * Unit-тест для `OptionForTicketTypeView::fromState()`.
 *
 * Read-модель для фронта формы покупки: уже с подмешанной ценой
 * (из option_price) и описанием (с pivot).
 */
class OptionForTicketTypeViewTest extends TestCase
{
    /** @test */
    public function it_builds_view_with_price_and_description(): void
    {
        $view = OptionForTicketTypeView::fromState([
            'id' => '11111111-1111-1111-1111-111111111111',
            'name' => 'Саженец',
            'price' => 500,
            'description' => 'Дадим саженец после фестиваля',
            'active' => true,
        ]);

        $this->assertSame('11111111-1111-1111-1111-111111111111', $view->getId()->value());
        $this->assertSame('Саженец', $view->getName());
        $this->assertSame(500, $view->getPrice());
        $this->assertIsInt($view->getPrice());
        $this->assertSame('Дадим саженец после фестиваля', $view->getDescription());
        $this->assertTrue($view->getActive());
    }

    /** @test */
    public function it_handles_null_description(): void
    {
        $view = OptionForTicketTypeView::fromState([
            'id' => '11111111-1111-1111-1111-111111111111',
            'name' => 'Без описания',
            'price' => 1000,
            'description' => null,
            'active' => true,
        ]);

        $this->assertNull($view->getDescription());
    }
}
