<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Создаём тип анкеты "Анкета нового пользователя"
        $newUserQuestionnaireTypeId = 'b2c3d4e5-f6a7-8901-bcde-f23456789012';

        // Проверяем, не создан ли уже такой тип
        $exists = DB::table('questionnaire_type')
            ->where('id', $newUserQuestionnaireTypeId)
            ->exists();

        if (!$exists) {
            DB::table('questionnaire_type')->insert([
                'id' => $newUserQuestionnaireTypeId,
                'name' => 'Анкета нового пользователя',
                'active' => true,
                'sort' => 1,
                'questions' => json_encode([
                    [
                        'title' => 'Возраст',
                        'name' => 'agy',
                        'type' => 'number',
                        'validate' => null,
                        'required' => true,
                    ],
                    [
                        'title' => 'Телефон',
                        'name' => 'phone',
                        'type' => 'string',
                        'validate' => '/^\\+?[0-9\\s\\-\\(\\)]+$/',
                        'required' => true,
                    ],
                    [
                        'title' => 'Email',
                        'name' => 'email',
                        'type' => 'string',
                        'validate' => '/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/',
                        'required' => true,
                    ],
                    [
                        'title' => 'Telegram',
                        'name' => 'telegram',
                        'type' => 'string',
                        'validate' => json_encode([
                            'rules' => ['string', 'min:5', 'max:32', 'regex:/^[a-zA-Z0-9_]+$/'],
                            'messages' => [
                                'min' => 'должен содержать минимум 5 символов.',
                                'max' => 'не может превышать 32 символа.',
                                'regex' => 'Разрешены только латинские буквы (a-z), цифры (0-9) и подчеркивание (_).',
                            ],
                        ]),
                        'required' => false,
                    ],
                    [
                        'title' => 'Вконтакте',
                        'name' => 'vk',
                        'type' => 'string',
                        'validate' => null,
                        'required' => false,
                    ],
                    [
                        'title' => 'Сколько раз ты уже бывал на Систо',
                        'name' => 'howManyTimes',
                        'type' => 'string',
                        'validate' => null,
                        'required' => false,
                    ],
                    [
                        'title' => 'Зачем ты едешь на Систо?',
                        'name' => 'questionForSysto',
                        'type' => 'text',
                        'validate' => null,
                        'required' => true,
                    ],
                    [
                        'title' => 'Стили музыки, которые предпочитаешь в лесу',
                        'name' => 'musicStyles',
                        'type' => 'text',
                        'validate' => null,
                        'required' => false,
                    ],
                    [
                        'title' => 'Имя на билете',
                        'name' => 'name',
                        'type' => 'string',
                        'validate' => null,
                        'required' => false,
                    ],
                    [
                        'title' => 'Откуда ты узнал о Систо',
                        'name' => 'whereSysto',
                        'type' => 'string',
                        'validate' => null,
                        'required' => false,
                    ],
                    [
                        'title' => 'Считаете ли вы себя участвующим в сотворении Систо',
                        'name' => 'creationOfSisto',
                        'type' => 'text',
                        'validate' => null,
                        'required' => false,
                    ],
                    [
                        'title' => 'Готовы принимать более активное или творческое участие в создании события',
                        'name' => 'activeOfEvent',
                        'type' => 'text',
                        'validate' => null,
                        'required' => false,
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Присваиваем тип "Анкета нового пользователя" всем анкетам без order_id и ticket_id
        DB::table('questionnaire')
            ->whereNull('order_id')
            ->where(function($query) {
                $query->whereNull('ticket_id')
                    ->orWhere('ticket_id', '');
            })
            ->whereNull('questionnaire_type_id')
            ->update(['questionnaire_type_id' => $newUserQuestionnaireTypeId]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Удаляем привязку типа у анкет нового пользователя
        DB::table('questionnaire')
            ->where('questionnaire_type_id', 'b2c3d4e5-f6a7-8901-bcde-f23456789012')
            ->update(['questionnaire_type_id' => null]);

        // Удаляем тип анкеты
        DB::table('questionnaire_type')
            ->where('id', 'b2c3d4e5-f6a7-8901-bcde-f23456789012')
            ->delete();
    }
};
