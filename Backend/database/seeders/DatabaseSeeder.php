<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use JsonException;

class DatabaseSeeder extends Seeder
{
    public function __construct(
        private TypeTicketsSeeder $typeTicketsSeeder,
        private TypesOfPaymentSeeder $typesOfPaymentSeeder,
        private PromoCodSeeder $promoCodSeeder,
        private UserSeeder $userSeeder,
        private OrderSeeder $orderSeeder,
        private CommentSeeder $commentSeeder,
        private FestivalSeeder $festivalSeeder,
        private TypeTicketsPriceSeeder $typeTicketsPriceSeeder,
    ) {
    }

    /**
     * Seed the application's database.
     *
     * @return void
     * @throws JsonException
     */
    public function run(): void
    {
        $this->festivalSeeder->run();
        $this->typeTicketsSeeder->run();
        $this->typesOfPaymentSeeder->run();
        $this->promoCodSeeder->run();
        $this->userSeeder->run();
        $this->orderSeeder->run();
        $this->commentSeeder->run();
        $this->typeTicketsPriceSeeder->run();
    }
}
