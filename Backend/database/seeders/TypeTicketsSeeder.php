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

    public const ID_FOR_MULTI_FESTIVAL = '222abc0c-fc8e-4a1d-a4b0-d345cafacf99';
    public const ID_FOR_NEXT_FESTIVAL = '37c6b8d8-e01e-4bc4-b7b8-fcaa422ab25f';

    PUBLIC CONST ID_LIVE_FOR_MULTI_FESTIVAL = '222abc0c-fc8e-4a1d-a4b0-d345cafacf00';
    PUBLIC CONST ID_LIVE_FOR_NEXT_FESTIVAL = '222abc0c-fc8e-4a1d-a4b0-d345cafacf01';

    public const DEFAULT_MULTI_FESTIVAL_PRICE = 7600;
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
        $ticketTypes->sort = 1;
        $ticketTypes->festivals()->attach(FestivalHelper::UUID_FESTIVAL);
        $ticketTypes->save();

        $ticketTypes = new TicketTypesModel();
        $ticketTypes->id = self::ID_FOR_REGIONS;
        $ticketTypes->name = 'Оргвзнос для регионов';
        $ticketTypes->price = '3600';
        $ticketTypes->sort = 2;
        $ticketTypes->festivals()->attach(FestivalHelper::UUID_FESTIVAL);
        $ticketTypes->save();

        $ticketTypes = new TicketTypesModel();
        $ticketTypes->id = self::ID_FOR_MULTI_FESTIVAL;
        $ticketTypes->name = 'Оргвзнос мульти фестиваль';
        $ticketTypes->price = self::DEFAULT_MULTI_FESTIVAL_PRICE;
        $ticketTypes->sort = 3;
        $ticketTypes->festivals()->attach(FestivalHelper::UUID_FESTIVAL);
        $ticketTypes->festivals()->attach(FestivalHelper::UUID_SECOND_FESTIVAL);
        $ticketTypes->save();

        $ticketTypes = new TicketTypesModel();
        $ticketTypes->id = self::ID_FOR_NEXT_FESTIVAL;
        $ticketTypes->name = 'Оргвзнос на осень';
        $ticketTypes->price = '4000';
        $ticketTypes->sort = 4;
        $ticketTypes->festivals()->attach(FestivalHelper::UUID_SECOND_FESTIVAL);
        $ticketTypes->save();

        $ticketTypes = new TicketTypesModel();
        $ticketTypes->id = self::ID_LIVE_FOR_MULTI_FESTIVAL;
        $ticketTypes->name = 'Оргвзнос Живой билет лесная карта';
        $ticketTypes->price = self::DEFAULT_MULTI_FESTIVAL_PRICE;
        $ticketTypes->sort = 5;
        $ticketTypes->is_live_ticket = true;
        $ticketTypes->festivals()->attach(FestivalHelper::UUID_FESTIVAL);
        $ticketTypes->festivals()->attach(FestivalHelper::UUID_SECOND_FESTIVAL);
        $ticketTypes->save();

        $ticketTypes = new TicketTypesModel();
        $ticketTypes->id = self::ID_LIVE_FOR_NEXT_FESTIVAL;
        $ticketTypes->name = 'Оргвзнос Живой билет';
        $ticketTypes->price = self::DEFAULT_PRICE;
        $ticketTypes->sort = 6;
        $ticketTypes->is_live_ticket = true;
        $ticketTypes->festivals()->attach(FestivalHelper::UUID_FESTIVAL);
        $ticketTypes->save();
    }
}
