<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function __construct(
        private TypeTicketsSeeder $typeTicketsSeeder,
        private TypesOfPaymentSeeder $typesOfPaymentSeeder,
        private PromoCodSeeder $promoCodSeeder,
        private UserSeeder $userSeeder,
    ){
    }

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->typeTicketsSeeder->run();
        $this->typesOfPaymentSeeder->run();
        $this->promoCodSeeder->run();
        $this->userSeeder->run();
    }
}
