<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const UUID = 'e5f6a7b8-c9d0-1234-ef01-567890123456';

    public function up(): void
    {
        if (!DB::table('questionnaire_type')->where('id', self::UUID)->exists()) {
            DB::table('questionnaire_type')->insert([
                'id'        => self::UUID,
                'name'      => 'Анкета участника куратора',
                'code'      => 'curator_participant',
                'active'    => true,
                'sort'      => 3,
                'questions' => json_encode([
                    [
                        'title'    => 'Имя и Фамилия участника',
                        'name'     => 'participantName',
                        'type'     => 'string',
                        'validate' => null,
                        'required' => true,
                    ],
                    [
                        'title'    => 'Контактные данные (телефон или telegram)',
                        'name'     => 'contact',
                        'type'     => 'string',
                        'validate' => null,
                        'required' => true,
                    ],
                    [
                        'title'    => 'Фото для бейджа',
                        'name'     => 'photo',
                        'type'     => 'file',
                        'validate' => null,
                        'required' => false,
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Привязываем тип анкеты к типам билетов с is_list_ticket=true (у которых ещё нет привязки)
        DB::table('ticket_type')
            ->where('is_list_ticket', true)
            ->whereNull('questionnaire_type_id')
            ->update(['questionnaire_type_id' => self::UUID]);
    }

    public function down(): void
    {
        DB::table('ticket_type')
            ->where('questionnaire_type_id', self::UUID)
            ->update(['questionnaire_type_id' => null]);

        DB::table('questionnaire_type')->where('id', self::UUID)->delete();
    }
};
