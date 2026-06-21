<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use Baza\Tickets\Applications\Search\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Релевантность поиска без QR (фикс «шляпы» на нечисловой запрос).
 *
 * Корень бага: legacy-репозитории делали `whereKilter((int)$q)` и `auto LIKE '%'.(int)$q.'%'`.
 * Для нечислового запроса `(int)"test" === 0` → искали по kilter=0 / номеру с нулём → мусор.
 * Фикс: числовой поиск только при ctype_digit; comment убран из гостевого поиска.
 * БД baza_test (phpunit.xml).
 */
class SearchRelevanceTest extends TestCase
{
    use RefreshDatabase;

    private const F = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';

    private function search(string $q): array
    {
        return app(SearchService::class)->find($q)->toArray();
    }

    private function el(int $kilter, string $name, string $email = 'g@example.ru', ?string $comment = null): void
    {
        DB::table('el_tickets')->insert([
            'kilter' => $kilter,
            'uuid' => sprintf('%08d-0000-4000-8000-000000000001', $kilter),
            'city' => 'Москва',
            'name' => $name,
            'email' => $email,
            'phone' => '+70000000000',
            'comment' => $comment,
            'date_order' => now(),
            'status' => 'paid',
            'type_ticket' => 'Электронный',
            'type_ticket_id' => '22222222-2222-2222-2222-222222222222',
            'is_need_seedling' => 0,
            'change_id' => null,
            'date_change' => null,
            'festival_id' => self::F,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function auto(string $plate): void
    {
        DB::table('auto')->insert([
            'curator' => '', 'project' => '', 'auto' => $plate, 'comment' => null,
            'festival_id' => self::F, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function live(int $kilter): void
    {
        DB::table('live_tickets')->insert([
            'kilter' => $kilter, 'status' => 'paid', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_text_query_does_not_match_via_zero_coercion(): void
    {
        // До фикса: live kilter=0 и парковка с нулём в номере матчились на ЛЮБОЙ нечисловой запрос.
        $this->live(0);
        $this->auto('А000АА777');
        $this->el(0, 'Нолик Гость');

        $r = $this->search('test');

        self::assertEmpty($r['live'] ?? [], 'live kilter=0 не должен матчиться на "test"');
        self::assertEmpty($r['auto'] ?? [], 'парковка с нулём в номере не должна матчиться на "test"');
        self::assertEmpty($r['electron'] ?? [], 'el kilter=0 не должен матчиться на "test"');
    }

    public function test_numeric_query_still_matches_kilter(): void
    {
        $this->el(101, 'Иван Электрон');
        $this->live(202);

        $rEl = $this->search('101');
        self::assertNotEmpty($rEl['electron'] ?? [], 'числовой поиск по номеру el должен работать');
        self::assertSame(101, $rEl['electron'][0]['kilter']);

        $rLive = $this->search('202');
        self::assertNotEmpty($rLive['live'] ?? [], 'числовой поиск по номеру live должен работать');
    }

    public function test_plate_searched_by_text_not_int(): void
    {
        $this->auto('А123АА777');

        // По цифрам номера — находит.
        self::assertNotEmpty($this->search('123')['auto'] ?? [], 'парковка ищется по цифрам номера');
        // По "test" — нет (раньше (int)test=0 → LIKE %0%).
        self::assertEmpty($this->search('test')['auto'] ?? [], '"test" не должен тянуть парковки');
    }

    public function test_comment_excluded_from_guest_search(): void
    {
        $this->el(303, 'Аноним Без Теста', 'a@b.ru', 'test служебная заметка');

        // comment содержит "test", но он убран из гостевого поиска → не находим.
        self::assertEmpty($this->search('test')['electron'] ?? [], 'comment не участвует в гостевом поиске');
    }

    public function test_email_match_is_intended(): void
    {
        // Документируем ОЖИДАЕМОЕ: поиск по email работает (демо-данные с @test.* — это норма).
        $this->el(404, 'Иван Почтовый', 'guest@test.com');

        self::assertNotEmpty($this->search('test')['electron'] ?? [], 'поиск по email — штатное поведение');
    }
}
