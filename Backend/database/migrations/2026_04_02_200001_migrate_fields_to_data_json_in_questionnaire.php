<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $questionnaires = DB::table('questionnaire')->get();

        foreach ($questionnaires as $questionnaire) {
            $data = json_encode([
                'order_id' => $questionnaire->order_id,
                'ticket_id' => $questionnaire->ticket_id,
                'user_id' => $questionnaire->user_id,
                'festival_id' => $questionnaire->festival_id,
                'status' => $questionnaire->status,
            ]);

            DB::table('questionnaire')
                ->where('id', $questionnaire->id)
                ->update(['data' => $data]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $questionnaires = DB::table('questionnaire')->whereNotNull('data')->get();

        foreach ($questionnaires as $questionnaire) {
            $data = json_decode($questionnaire->data, true);

            if ($data) {
                DB::table('questionnaire')
                    ->where('id', $questionnaire->id)
                    ->update([
                        'order_id' => $data['order_id'] ?? null,
                        'ticket_id' => $data['ticket_id'] ?? null,
                        'user_id' => $data['user_id'] ?? null,
                        'festival_id' => $data['festival_id'] ?? null,
                        'status' => $data['status'] ?? null,
                    ]);
            }
        }
    }
};
