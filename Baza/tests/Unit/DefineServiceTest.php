<?php

namespace Tests\Unit;

use Baza\Shared\Domain\ValueObject\Uuid;
use Baza\Tickets\Services\DefineService;
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
     * A basic unit test example.
     *
     * @return void
     */
    public function test_in_correct_type(): void
    {
        $result = $this->service->getTypeByReference('http://baza.spaceofjoy.ru/live?id=0020');
        self::assertEquals(DefineService::LIVE_TICKET, $result->getType());
        self::assertEquals(20, $result->getId());
        $result = $this->service->getTypeByReference('/live?id=0020');
        self::assertEquals(DefineService::LIVE_TICKET, $result->getType());
        self::assertEquals(20, $result->getId());


        $result = $this->service->getTypeByReference('/search?q=sS50065');
        self::assertEquals(DefineService::SPISOK_TICKET, $result->getType());
        self::assertEquals(50065, $result->getId());

        $result = $this->service->getTypeByReference('http://baza.spaceofjoy.ru/search?q=sS50065');
        self::assertEquals(DefineService::SPISOK_TICKET, $result->getType());
        self::assertEquals(50065, $result->getId());

        $result = $this->service->getTypeByReference('/search?q=ff30049');
        self::assertEquals(DefineService::DRUG_TICKET, $result->getType());
        self::assertEquals(30049, $result->getId());
        $result = $this->service->getTypeByReference('http://baza.spaceofjoy.ru/search?q=ff30049');
        self::assertEquals(DefineService::DRUG_TICKET, $result->getType());
        self::assertEquals(30049, $result->getId());

        $result = $this->service->getTypeByReference('/newTickets/ab5ad7e7-be27-47c3-9900-65e1e3ea8cd7');
        self::assertEquals(DefineService::ELECTRON_TICKET, $result->getType());
        self::assertTrue((new Uuid('ab5ad7e7-be27-47c3-9900-65e1e3ea8cd7'))->equals($result->getId()));
        $result = $this->service->getTypeByReference('http://baza.spaceofjoy.ru/newTickets/ab5ad7e7-be27-47c3-9900-65e1e3ea8cd7');
        self::assertEquals(DefineService::ELECTRON_TICKET, $result->getType());
        self::assertTrue((new Uuid('ab5ad7e7-be27-47c3-9900-65e1e3ea8cd7'))->equals($result->getId()));
    }
}
