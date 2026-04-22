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
        $excludedFields = ['id', 'order_id', 'ticket_id', 'user_id', 'festival_id', 'status', 'created_at', 'updated_at', 'data', 'email', 'telegram'];

        $questionnaires = DB::table('questionnaire')->get();

        foreach ($questionnaires as $questionnaire) {
            $data = [];

            foreach ($questionnaire as $field => $value) {
                if (!in_array($field, $excludedFields)) {
                    $data[$field] = $value;
                }
            }

            DB::table('questionnaire')
                ->where('id', $questionnaire->id)
                ->update(['data' => json_encode($data)]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $excludedFields = ['id', 'order_id', 'ticket_id', 'user_id', 'festival_id', 'status', 'created_at', 'updated_at', 'data', 'email', 'telegram'];

        $questionnaires = DB::table('questionnaire')->whereNotNull('data')->get();

        foreach ($questionnaires as $questionnaire) {
            $data = json_decode($questionnaire->data, true);

            if ($data) {
                $updateData = [];

                foreach ($data as $field => $value) {
                    if (!in_array($field, $excludedFields)) {
                        $updateData[$field] = $value;
                    }
                }

                DB::table('questionnaire')
                    ->where('id', $questionnaire->id)
                    ->update($updateData);
            }
        }
    }
};
