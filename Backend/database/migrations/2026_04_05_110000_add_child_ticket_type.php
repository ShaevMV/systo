<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

return new class extends Migration
{
    public const CHILD_TICKET_ID = 'c3d4e5f6-a7b8-9012-cdef-345678901235';
    public const CHILD_QUESTIONNAIRE_TYPE_ID = 'c3d4e5f6-a7b8-9012-cdef-345678901234';
    public const CHILD_TICKET_PRICE = 400;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Проверяем, не создан ли уже такой тип билета
        $exists = DB::table('ticket_type')
            ->where('id', self::CHILD_TICKET_ID)
            ->exists();

        if (!$exists) {
            DB::table('ticket_type')->insert([
                'id' => self::CHILD_TICKET_ID,
                'name' => 'Детский билет',
                'price' => self::CHILD_TICKET_PRICE,
                'sort' => 7,
                'active' => true,
                'is_live_ticket' => false,
                'questionnaire_type_id' => self::CHILD_QUESTIONNAIRE_TYPE_ID,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Привязываем к основному фестивалю
            DB::table('ticket_type_festival')->insert([
                'festival_id' => FestivalHelper::UUID_FESTIVAL,
                'ticket_type_id' => self::CHILD_TICKET_ID,
                'pdf' => 'TypeTicketPdfChild.black.php',
                'email' => 'TypeTicketMailOrderToPaidChild.black.php',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('ticket_type_festival')
            ->where('ticket_type_id', self::CHILD_TICKET_ID)
            ->delete();

        DB::table('ticket_type')
            ->where('id', self::CHILD_TICKET_ID)
            ->delete();
    }
};
