<?php

declare(strict_types=1);

namespace Tests\Feature\Questionnaire;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tickets\Questionnaire\Domain\ValueObject\QuestionnaireStatus;

/**
 * Feature тест для проверки что API возвращает поля из JSON-колонки `data`.
 *
 * Проверяет:
 * - DTO correctly maps fields from JSON `data` to response
 * - API endpoint returns phone, telegram, vk, email from `data` column
 */
class QuestionnaireDataFieldsApiTest extends TestCase
{
    private const TEST_QUESTIONNAIRE_TYPE_ID = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
    private const TEST_ORDER_ID = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';
    private const TEST_TICKET_ID = 'cccccccc-cccc-cccc-cccc-cccccccccccc';
    private const TEST_USER_ID = 'dddddddd-dddd-dddd-dddd-dddddddddddd';

    protected function setUp(): void
    {
        parent::setUp();

        // Очищаем тестовые данные перед каждым тестом
        DB::table('questionnaire')->whereIn('id', [10001, 10002, 10003])->delete();
    }

    /** @test */
    public function it_returns_fields_from_json_data_column(): void
    {
        // Создаём анкету где данные ТОЛЬКО в JSON `data`
        DB::table('questionnaire')->insert([
            'id' => 10001,
            'order_id' => self::TEST_ORDER_ID,
            'ticket_id' => self::TEST_TICKET_ID,
            'user_id' => self::TEST_USER_ID,
            'questionnaire_type_id' => self::TEST_QUESTIONNAIRE_TYPE_ID,
            'status' => QuestionnaireStatus::NEW,
            // Корневые поля NULL
            'email' => null,
            'phone' => null,
            'telegram' => null,
            'data' => json_encode([
                'email' => 'test@example.com',
                'phone' => '+79991234567',
                'telegram' => 'testuser',
                'vk' => 'https://vk.com/testuser',
                'agy' => 25,
                'is_have_in_club' => true,
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Вызываем API получения списка анкет
        $response = $this->postJson('/api/v1/questionnaire/load', [
            'email' => null,
            'telegram' => null,
            'vk' => null,
            'status' => null,
            'is_have_in_club' => null,
            'questionnaire_type_id' => null,
        ]);

        // Проверяем что API вернул данные из JSON `data`
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $questionnaireList = $response->json('questionnaireList');
        
        $this->assertIsArray($questionnaireList);
        $this->assertNotEmpty($questionnaireList);

        // Находим нашу тестовую анкету
        $testQuestionnaire = null;
        foreach ($questionnaireList as $item) {
            if ($item['id'] === 10001) {
                $testQuestionnaire = $item;
                break;
            }
        }

        $this->assertNotNull($testQuestionnaire, 'Тестовая анкета не найдена в ответе API');

        // КЛЮЧЕВАЯ ПРОВЕРКА: поля из JSON `data` должны быть в ответе
        $this->assertEquals('+79991234567', $testQuestionnaire['phone'], 
            'Поле phone из JSON data должно быть в ответе API');
        $this->assertEquals('testuser', $testQuestionnaire['telegram'],
            'Поле telegram из JSON data должно быть в ответе API');
        $this->assertEquals('https://vk.com/testuser', $testQuestionnaire['vk'],
            'Поле vk из JSON data должно быть в ответе API');
        $this->assertEquals('test@example.com', $testQuestionnaire['email'],
            'Поле email из JSON data должно быть в ответе API');
        $this->assertEquals(25, $testQuestionnaire['agy'],
            'Поле agy из JSON data должно быть в ответе API');
        $this->assertTrue($testQuestionnaire['is_have_in_club'],
            'Поле is_have_in_club из JSON data должно быть в ответе API');
    }

    /** @test */
    public function it_handles_empty_fields_correctly(): void
    {
        // Анкета с минимальными данными
        DB::table('questionnaire')->insert([
            'id' => 10002,
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
                // telegram и vk ОТСУТСТВУЮТ в data
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/questionnaire/load', []);

        $response->assertStatus(200);

        $questionnaireList = $response->json('questionnaireList');
        $testQuestionnaire = null;
        foreach ($questionnaireList as $item) {
            if ($item['id'] === 10002) {
                $testQuestionnaire = $item;
                break;
            }
        }

        $this->assertNotNull($testQuestionnaire);
        
        // phone должен быть из data
        $this->assertEquals('+79991112233', $testQuestionnaire['phone']);
        
        // telegram и vk должны быть null (отсутствуют в data)
        $this->assertNull($testQuestionnaire['telegram']);
        $this->assertNull($testQuestionnaire['vk']);
    }
}
