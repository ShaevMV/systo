<?php

namespace Tests\Unit;

use Shared\Domain\ValueObject\Uuid;
use Baza\Shared\Services\DefineService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DefineServiceTest extends TestCase
{
    private DefineService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DefineService();
    }

    /**
     * Электронный билет — распознаётся по URL /newTickets/{uuid}.
     */
    public function test_electron_ticket_by_relative_url(): void
    {
        $result = $this->service->getTypeByReference('/newTickets/ab5ad7e7-be27-47c3-9900-65e1e3ea8cd7');
        self::assertEquals(DefineService::ELECTRON_TICKET, $result->getType());
        self::assertTrue((new Uuid('ab5ad7e7-be27-47c3-9900-65e1e3ea8cd7'))->equals($result->getId()));
    }

    public function test_electron_ticket_by_full_url(): void
    {
        $result = $this->service->getTypeByReference('http://baza.spaceofjoy.ru/newTickets/ab5ad7e7-be27-47c3-9900-65e1e3ea8cd7');
        self::assertEquals(DefineService::ELECTRON_TICKET, $result->getType());
        self::assertTrue((new Uuid('ab5ad7e7-be27-47c3-9900-65e1e3ea8cd7'))->equals($result->getId()));
    }

    /**
     * Старые форматы ссылок ('0020' для LIVE, '/search?q=sS{id}' для SPISOK,
     * '/search?q=ff{id}' для DRUG) больше не поддерживаются — выбрасывается
     * InvalidArgumentException.
     */
    public function test_legacy_formats_throw_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getTypeByReference('0020');
    }
}
