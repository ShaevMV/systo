<?php

declare(strict_types=1);

namespace Tests\Unit\Questionnaire\Dto;

use Tests\TestCase;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;
use Tickets\Questionnaire\Domain\ValueObject\QuestionnaireStatus;

/**
 * Unit тест для QuestionnaireTicketDto::fromState() и toArray().
 *
 * Проверяет что:
 * - Поля из JSON `data` правильно маппятся при fromState()
 * - toArray() возвращает поля из data (а не null из корневых колонок)
 */
class QuestionnaireTicketDtoTest extends TestCase
{
    /** @test */
    public function it_maps_fields_from_json_data_column(): void
    {
        // Имитируем данные из БД где корневые колонки NULL, а данные в JSON
        $rawDataFromDatabase = [
            'id' => 10001,
            'order_id' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            'ticket_id' => 'cccccccc-cccc-cccc-cccc-cccccccccccc',
            'user_id' => 'dddddddd-dddd-dddd-dddd-dddddddddddd',
            'questionnaire_type_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'status' => QuestionnaireStatus::NEW,
            // Корневые поля NULL
            'email' => null,
            'phone' => null,
            'telegram' => null,
            // JSON колонка с данными
            'data' => [
                'email' => 'json_email@example.com',
                'phone' => '+79991234567',
                'telegram' => 'json_telegram_user',
                'vk' => 'https://vk.com/json_user',
                'agy' => 30,
                'name' => 'Тестовый Пользователь',
                'is_have_in_club' => true,
                // Поля детской анкеты
                'childName' => 'Ребёнок Тестов',
                'childAge' => 7,
            ],
        ];

        // Создаём DTO из состояния
        $dto = QuestionnaireTicketDto::fromState($rawDataFromDatabase);

        // Конвертируем обратно в массив
        $result = $dto->toArray();

        // Проверяем что поля из JSON `data` правильно маппятся
        $this->assertEquals('+79991234567', $result['phone'], 
            'phone должен браться из JSON data');
        $this->assertEquals('json_telegram_user', $result['telegram'],
            'telegram должен браться из JSON data');
        $this->assertEquals('https://vk.com/json_user', $result['vk'],
            'vk должен браться из JSON data');
        $this->assertEquals('json_email@example.com', $result['email'],
            'email должен браться из JSON data');
        $this->assertEquals(30, $result['agy'],
            'agy должен браться из JSON data');
        $this->assertEquals('Тестовый Пользователь', $result['name'],
            'name должен браться из JSON data');
        $this->assertTrue($result['is_have_in_club'],
            'is_have_in_club должен браться из JSON data');

        // Проверяем что динамические поля детской анкеты тоже есть
        $this->assertEquals('Ребёнок Тестов', $result['childName'],
            'childName должно быть в extraData');
        $this->assertEquals(7, $result['childAge'],
            'childAge должно быть в extraData');
    }

    /** @test */
    public function it_prioritizes_json_data_over_root_fields(): void
    {
        // Ситуация где есть и корневые поля И данные в JSON
        $rawData = [
            'id' => 10002,
            'order_id' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            'ticket_id' => 'cccccccc-cccc-cccc-cccc-cccccccccccc',
            'user_id' => 'dddddddd-dddd-dddd-dddd-dddddddddddd',
            'status' => QuestionnaireStatus::APPROVE,
            // Корневые поля (старые)
            'email' => 'old@example.com',
            'phone' => '+79990000000',
            'telegram' => 'old_telegram',
            // JSON с новыми данными (должны иметь приоритет)
            'data' => [
                'email' => 'new@example.com',
                'phone' => '+79991111111',
                'telegram' => 'new_telegram',
                'vk' => 'https://vk.com/new_user',
            ],
        ];

        $dto = QuestionnaireTicketDto::fromState($rawData);
        $result = $dto->toArray();

        // JSON data имеет приоритет
        $this->assertEquals('+79991111111', $result['phone'],
            'phone из JSON data имеет приоритет над корневым');
        $this->assertEquals('new_telegram', $result['telegram'],
            'telegram из JSON data имеет приоритет над корневым');
        $this->assertEquals('new@example.com', $result['email'],
            'email из JSON data имеет приоритет над корневым');
        $this->assertEquals('https://vk.com/new_user', $result['vk']);
    }

    /** @test */
    public function it_handles_null_fields_gracefully(): void
    {
        $rawData = [
            'id' => 10003,
            'order_id' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            'ticket_id' => 'cccccccc-cccc-cccc-cccc-cccccccccccc',
            'status' => QuestionnaireStatus::NEW,
            'data' => [
                'email' => 'only_email@example.com',
                'phone' => '+79992223344',
                // telegram, vk, agy и др. ОТСУТСТВУЮТ
            ],
        ];

        $dto = QuestionnaireTicketDto::fromState($rawData);
        $result = $dto->toArray();

        // Есть данные
        $this->assertEquals('+79992223344', $result['phone']);
        $this->assertEquals('only_email@example.com', $result['email']);

        // Отсутствующие поля = null
        $this->assertNull($result['telegram']);
        $this->assertNull($result['vk']);
        $this->assertNull($result['agy']);
        $this->assertNull($result['name']);
    }

    /** @test */
    public function it_handles_string_json_data(): void
    {
        // Ситуация когда data пришла как строка (не распаршена)
        $rawData = [
            'id' => 10004,
            'order_id' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            'status' => QuestionnaireStatus::NEW,
            'data' => json_encode([
                'email' => 'string_json@example.com',
                'phone' => '+79993334455',
                'telegram' => 'from_string',
            ], JSON_UNESCAPED_UNICODE),
        ];

        $dto = QuestionnaireTicketDto::fromState($rawData);
        $result = $dto->toArray();

        $this->assertEquals('+79993334455', $result['phone']);
        $this->assertEquals('from_string', $result['telegram']);
        $this->assertEquals('string_json@example.com', $result['email']);
    }
}
