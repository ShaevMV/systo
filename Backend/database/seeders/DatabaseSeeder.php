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
        private TypeTicketsSecondFestivalSeeder $secondFestivalSeeder,
        private TypeTicketsGroupSeeder $groupSeeder,
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
        $this->promoCodSeeder->run();
        $this->typesOfPaymentSeeder->run();

        $this->festivalSeeder->run();
        $this->typeTicketsSeeder->run();
        $this->typeTicketsPriceSeeder->run();

        $this->userSeeder->run();
        $this->orderSeeder->run();
        $this->commentSeeder->run();
        $this->secondFestivalSeeder->run();
        $this->groupSeeder->run();
    }
}
