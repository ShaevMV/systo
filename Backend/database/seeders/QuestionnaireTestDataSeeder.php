<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tickets\Questionnaire\Domain\ValueObject\QuestionnaireStatus;

/**
 * Сидер для создания тестовых анкет с данными в JSON-колонке `data`.
 *
 * Используется для acceptance-тестов, проверяющих что поля из `data`
 * (phone, telegram, vk и др.) правильно выводятся в админке.
 */
class QuestionnaireTestDataSeeder extends Seeder
{
    // Тестовый UUID типа анкеты "guest" (если есть в БД)
    // Если нет — создадим свой
    private const TEST_QUESTIONNAIRE_TYPE_ID = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';

    private const TEST_ORDER_ID = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';
    private const TEST_TICKET_ID = 'cccccccc-cccc-cccc-cccc-cccccccccccc';
    private const TEST_USER_ID = 'dddddddd-dddd-dddd-dddd-dddddddddddd';

    public function run(): void
    {
        // Анкета 1: все данные только в JSON data
        DB::table('questionnaire')->insert([
            'id' => 10001,
            'order_id' => self::TEST_ORDER_ID,
            'ticket_id' => self::TEST_TICKET_ID,
            'user_id' => self::TEST_USER_ID,
            'questionnaire_type_id' => self::TEST_QUESTIONNAIRE_TYPE_ID,
            'status' => QuestionnaireStatus::NEW,
            // Корневые поля EMPTY — данные только в data JSON
            'email' => null,
            'phone' => null,
            'telegram' => null,
            'data' => json_encode([
                'email' => 'test1@example.com',
                'phone' => '+79991234567',
                'telegram' => 'testuser1',
                'vk' => 'https://vk.com/testuser1',
                'agy' => 25,
                'name' => 'Иван Тестов',
                'is_have_in_club' => true,
                'childName' => 'Ребёнок Тестов', // Поле детской анкеты
                'childAge' => 7,
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);

        // Анкета 2: данные только в JSON data, другой статус
        DB::table('questionnaire')->insert([
            'id' => 10002,
            'order_id' => self::TEST_ORDER_ID,
            'ticket_id' => self::TEST_TICKET_ID,
            'user_id' => self::TEST_USER_ID,
            'questionnaire_type_id' => self::TEST_QUESTIONNAIRE_TYPE_ID,
            'status' => QuestionnaireStatus::APPROVE,
            'email' => null,
            'phone' => null,
            'telegram' => null,
            'data' => json_encode([
                'email' => 'test2@example.com',
                'phone' => '+79997654321',
                'telegram' => 'approved_user',
                'vk' => 'https://vk.com/approved',
                'agy' => 30,
                'name' => 'Анна Одобрена',
                'is_have_in_club' => false,
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);

        // Анкета 3: минимальные данные (только email и phone в data)
        DB::table('questionnaire')->insert([
            'id' => 10003,
            'order_id' => self::TEST_ORDER_ID,
            'ticket_id' => self::TEST_TICKET_ID,
            'user_id' => self::TEST_USER_ID,
            'questionnaire_type_id' => self::TEST_QUESTIONNAIRE_TYPE_ID,
            'status' => QuestionnaireStatus::NEW,
            'email' => null,
            'phone' => null,
            'telegram' => null,
            'data' => json_encode([
                'email' => 'minimal@example.com',
                'phone' => '+79991112233',
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);

        // Выводим сообщение только при запуске из командной строки
        if ($this->command !== null) {
            $this->command->info('✅ Создано 3 тестовые анкеты с данными в JSON-колонке data');
        }
    }
}
