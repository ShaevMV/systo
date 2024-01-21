<?php

namespace Database\Seeders;

use App\Models\Ordering\InfoForOrder\TicketTypesModel;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

class TypeTicketsGroupSeeder extends Seeder
{
    public const ID_FOR_FIRST_WAVE = '222abc0c-fc8e-4a1d-a4b0-d345cafacf77';
    public const DEFAULT_PRICE = 24000;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $ticketTypes = new TicketTypesModel();
        $ticketTypes->id = self::ID_FOR_FIRST_WAVE;
        $ticketTypes->name = 'Оргвзнос для регионов';
        $ticketTypes->price = self::DEFAULT_PRICE;
        $ticketTypes->festival()->attach(FestivalHelper::UUID_FESTIVAL);
        $ticketTypes->save();
    }
}
