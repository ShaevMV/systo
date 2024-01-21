<?php

namespace Database\Seeders;

use App\Models\Ordering\InfoForOrder\TicketTypesModel;
use Illuminate\Database\Seeder;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

class TypeTicketsSecondFestivalSeeder extends Seeder
{
    public const TYPE_TICKET_FOR_SECOND_FESTIVAL = '222abc0c-fc8e-4a1d-a4b0-d345cafa0923';
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $ticketTypes = new TicketTypesModel();
        $ticketTypes->id = self::TYPE_TICKET_FOR_SECOND_FESTIVAL;
        $ticketTypes->name = 'Оргвзнос';
        $ticketTypes->sort = 1;
        $ticketTypes->price = 3500;
        $ticketTypes->festival()->attach(FestivalHelper::UUID_SECOND_FESTIVAL);
        $ticketTypes->festival()->attach(FestivalHelper::UUID_FESTIVAL);
        $ticketTypes->save();

    }
}
