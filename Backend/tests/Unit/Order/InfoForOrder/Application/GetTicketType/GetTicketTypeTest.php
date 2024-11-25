<?php

declare(strict_types=1);

namespace Unit\Order\InfoForOrder\Application\GetTicketType;

use Database\Seeders\TypeTicketsSeeder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Order\InfoForOrder\Application\GetTicketType\GetTicketType;

class GetTicketTypeTest extends TestCase
{
    private GetTicketType $getTicketType;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();


        /** @var GetTicketType $getTicketType */
        $getTicketType = $this->app->get(GetTicketType::class);
        $this->getTicketType = $getTicketType;
    }

    public function test_correct_festival_in_type_ticket(): void
    {
        $r = $this->getTicketType->getTicketsTypeByUuid(new Uuid(TypeTicketsSeeder::ID_FOR_MULTI_FESTIVAL));
        self::assertCount(2, $r->getFestivalListId());
    }
}
