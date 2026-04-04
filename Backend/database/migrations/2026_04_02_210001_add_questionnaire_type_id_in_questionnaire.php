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
        Schema::table('questionnaire', function (Blueprint $table) {
            $table->uuid('questionnaire_type_id')->nullable()->default(null)->after('id')->comment('ID типа анкеты');
        });

        // Создаём тип анкеты "Гостевая анкета"
        $guestQuestionnaireTypeId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        
        DB::table('questionnaire_type')->insert([
            'id' => $guestQuestionnaireTypeId,
            'name' => 'Гостевая анкета',
            'active' => true,
            'sort' => 0,
            'questions' => json_encode([
                [
                    'title' => 'Возраст',
                    'name' => 'agy',
                    'type' => 'number',
                    'validate' => null,
                    'required' => true,
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
                    'required' => false,
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
                [
                    'title' => 'Хочет участвовать в клубе',
                    'name' => 'is_have_in_club',
                    'type' => 'number',
                    'validate' => null,
                    'required' => false,
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Связываем все существующие анкеты с типом "Гостевая анкета"
        DB::table('questionnaire')->update(['questionnaire_type_id' => $guestQuestionnaireTypeId]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questionnaire', function (Blueprint $table) {
            $table->dropColumn('questionnaire_type_id');
        });

        DB::table('questionnaire_type')->where('name', 'Гостевая анкета')->delete();
    }
};
