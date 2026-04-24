<?php

declare(strict_types=1);

namespace Tests\Unit\Questionnaire\Migration;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Unit тест для миграции 2026_04_23_000004_add_curator_participant_questionnaire_type.
 *
 * Проверяет что после применения миграции:
 * - В таблице questionnaire_type появилась запись с code=curator_participant
 * - Запись содержит ровно 3 вопроса
 * - Вопросы содержат обязательные поля participantName и contact
 * - Вопрос photo присутствует и не является обязательным
 *
 * RefreshDatabase (из TestCase) накатывает миграции перед каждым тестом,
 * поэтому миграция уже применена — мы проверяем результат её работы.
 */
class CuratorParticipantMigrationTest extends TestCase
{
    private const EXPECTED_UUID = 'e5f6a7b8-c9d0-1234-ef01-567890123456';
    private const EXPECTED_CODE = 'curator_participant';

    /**
     * Сценарий: после up() тип анкеты curator_participant существует в БД.
     *
     * @test
     */
    public function migration_creates_curator_participant_questionnaire_type(): void
    {
        $row = DB::table('questionnaire_type')
            ->where('id', self::EXPECTED_UUID)
            ->first();

        $this->assertNotNull($row, 'Тип анкеты curator_participant должен существовать в таблице questionnaire_type');
        $this->assertEquals(self::EXPECTED_CODE, $row->code, 'code должен быть curator_participant');
        $this->assertEquals('Анкета участника куратора', $row->name, 'name должно совпадать');
        $this->assertEquals(1, (int) $row->active, 'active должно быть true (1)');
    }

    /**
     * Сценарий: тип анкеты найден по code (не только по UUID).
     *
     * @test
     */
    public function migration_questionnaire_type_is_findable_by_code(): void
    {
        $row = DB::table('questionnaire_type')
            ->where('code', self::EXPECTED_CODE)
            ->where('active', true)
            ->first();

        $this->assertNotNull($row, 'Запись должна находиться по code и active=true');
        $this->assertEquals(self::EXPECTED_UUID, $row->id);
    }

    /**
     * Сценарий: в questions ровно 3 элемента.
     *
     * @test
     */
    public function migration_questionnaire_type_has_exactly_three_questions(): void
    {
        $row = DB::table('questionnaire_type')
            ->where('id', self::EXPECTED_UUID)
            ->first();

        $this->assertNotNull($row);

        $questions = json_decode($row->questions, true);
        $this->assertIsArray($questions, 'questions должно быть JSON-массивом');
        $this->assertCount(3, $questions, 'Должно быть ровно 3 вопроса');
    }

    /**
     * Сценарий: вопрос participantName существует и является обязательным.
     *
     * @test
     */
    public function migration_questions_contain_required_participant_name(): void
    {
        $row = DB::table('questionnaire_type')
            ->where('id', self::EXPECTED_UUID)
            ->first();

        $questions = json_decode($row->questions, true);
        $names = array_column($questions, 'name');

        $this->assertContains('participantName', $names, 'Вопрос participantName должен присутствовать');

        $participantNameQuestion = $questions[array_search('participantName', $names)];
        $this->assertTrue($participantNameQuestion['required'], 'participantName должен быть обязательным');
        $this->assertEquals('string', $participantNameQuestion['type']);
    }

    /**
     * Сценарий: вопрос contact существует и является обязательным.
     *
     * @test
     */
    public function migration_questions_contain_required_contact(): void
    {
        $row = DB::table('questionnaire_type')
            ->where('id', self::EXPECTED_UUID)
            ->first();

        $questions = json_decode($row->questions, true);
        $names = array_column($questions, 'name');

        $this->assertContains('contact', $names, 'Вопрос contact должен присутствовать');

        $contactQuestion = $questions[array_search('contact', $names)];
        $this->assertTrue($contactQuestion['required'], 'contact должен быть обязательным');
        $this->assertEquals('string', $contactQuestion['type']);
    }

    /**
     * Сценарий: вопрос photo существует, тип file, и НЕ является обязательным.
     *
     * @test
     */
    public function migration_questions_contain_optional_photo_field(): void
    {
        $row = DB::table('questionnaire_type')
            ->where('id', self::EXPECTED_UUID)
            ->first();

        $questions = json_decode($row->questions, true);
        $names = array_column($questions, 'name');

        $this->assertContains('photo', $names, 'Вопрос photo должен присутствовать');

        $photoQuestion = $questions[array_search('photo', $names)];
        $this->assertFalse($photoQuestion['required'], 'photo не должен быть обязательным');
        $this->assertEquals('file', $photoQuestion['type'], 'photo должен иметь тип file');
    }
}
