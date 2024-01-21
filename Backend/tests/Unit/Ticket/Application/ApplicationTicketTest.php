<?php

namespace Tests\Unit\Ticket\Application;

use Database\Seeders\FestivalSeeder;
use Database\Seeders\OrderSeeder;
use Endroid\QrCode\Exception\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Throwable;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Ticket\CreateTickets\Application\TicketApplication;

class ApplicationTicketTest extends TestCase
{
    use DatabaseTransactions;

    private TicketApplication $TicketApplication;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();
        /** @var TicketApplication $createTicketApplication */
        $createTicketApplication = $this->app->get(TicketApplication::class);
        $this->TicketApplication = $createTicketApplication;
    }

    /**
     * @throws Throwable
     */
    public function test_in_create_pdf(): void
    {
        $tickets = $this->TicketApplication->createList(
            new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER),
            [
                new GuestsDto(
                    'test',
                    new Uuid(OrderSeeder::ID_FOR_FIRST_TICKET),
                    new Uuid(FestivalHelper::UUID_FESTIVAL),
                ),
            ]
        );
        self::assertNotEmpty($tickets);
        self::assertCount(1, $tickets);
    }

    public function test_in_get_list_pdf(): void
    {
        $tickets = $this->TicketApplication->getPdfList(
            new Uuid(OrderSeeder::ID_FOR_FIRST_TICKET)
        );

        self::assertEquals(
            'http://localhost/storage/tickets/56f04400-02ab-4cbe-bfd4-4f7dda23d675.pdf',
            $tickets->getUrls()[0]
        );
    }


    /**
     * @throws ValidationException     *
     */
    public function test_in_create_QR_code(): void
    {
        $this->expectNotToPerformAssertions();
        /*$service = new CreatingQrCodeService();
        for($i = 1;$i<=2500;$i++) {
            $number = $this->addZero($i);
            $qrCode = $service->createQrCode($number);
            $qrCode->saveToFile(__DIR__.'/QR/'.$number.".png");
        }*/
    }


    private function addZero(int $number): string
    {
        $zero = '';

        if ($number < 1000) {
            $zero.='0';
        }
        if ($number < 100) {
            $zero.='0';
        }
        if ($number < 10) {
            $zero.='0';
        }

        return $zero.$number;
    }
}
