<?php

namespace Database\Seeders;

use App\Models\Ordering\InfoForOrder\TicketTypesModel;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

class TypeTicketsSeeder extends Seeder
{
    public const ID_FOR_FIRST_WAVE = '222abc0c-fc8e-4a1d-a4b0-d345cafacf95';
    public const DEFAULT_PRICE = 3800;
    public const ID_FOR_REGIONS = '37c6b8d8-e01e-4bc4-b7b8-fcaa422ab25b';
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $ticketTypes = new TicketTypesModel();
        $ticketTypes->id = self::ID_FOR_FIRST_WAVE;
        $ticketTypes->name = 'Оргвзнос';
        $ticketTypes->price = self::DEFAULT_PRICE;
        $ticketTypes->festival()->attach(FestivalHelper::UUID_FESTIVAL);
        $ticketTypes->save();

        $ticketTypes = new TicketTypesModel();
        $ticketTypes->id = self::ID_FOR_REGIONS;
        $ticketTypes->name = 'Оргвзнос для регионов';
        $ticketTypes->price = '3600';
        $ticketTypes->festival()->attach(FestivalHelper::UUID_FESTIVAL);
        $ticketTypes->save();
    }
}
