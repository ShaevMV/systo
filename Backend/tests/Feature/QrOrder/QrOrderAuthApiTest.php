<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Аутентификация S2S-канала приёма заказов qr→org (POST /api/v1/qrOrder/create):
 * канал закрыт сервисным ключом qr (заголовок X-QR-Token, middleware qr.ingest).
 * Без валидного ключа — 401, заказ не принимается. Список ключей даёт ротацию без простоя.
 */
class QrOrderAuthApiTest extends TestCase
{
    use WithQrIngestToken;

    protected function setUp(): void
    {
        parent::setUp();
        // Контракт приходит оплаченным → приём запускает выдачу; в тестах приёма её фейкаем.
        Queue::fake();
    }

    private function contract(): array
    {
        return [
            'order_id' => '11111111-1111-1111-1111-111111111111',
            'user' => ['city' => 'Москва', 'phone' => '+70000000000'],
            'price' => ['total' => 4000],
            'order_data' => [
                'type_order' => 'regular',
                'festival' => ['id' => '55555555-5555-5555-5555-555555555555', 'title' => 'Систо'],
                'status' => 'создан',
                'email' => 'buyer@example.com',
            ],
            'guests' => [
                ['name' => 'Иван Гость', 'email' => 'guest@example.com',
                 'type_ticket' => ['id' => '44444444-4444-4444-4444-444444444444', 'title' => 'Оргвзнос', 'options' => []]],
            ],
        ];
    }

    public function test_rejects_request_without_token(): void
    {
        $this->configureQrIngestToken();

        // Нет заголовка X-QR-Token → 401, заказ не создаётся.
        $this->postJson('/api/v1/qrOrder/create', $this->contract())
            ->assertStatus(401)
            ->assertJson(['success' => false]);

        $this->assertDatabaseMissing('qr_orders', ['id' => '11111111-1111-1111-1111-111111111111']);
    }

    public function test_rejects_request_with_wrong_token(): void
    {
        $this->configureQrIngestToken();

        // Неверный ключ → 401.
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), ['X-QR-Token' => 'wrong-key'])
            ->assertStatus(401)
            ->assertJson(['success' => false]);

        $this->assertDatabaseMissing('qr_orders', ['id' => '11111111-1111-1111-1111-111111111111']);
    }

    public function test_accepts_request_with_valid_token(): void
    {
        $this->configureQrIngestToken();

        // Верный ключ → заказ принимается (200).
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), $this->qrIngestHeaders())
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('qr_orders', ['id' => '11111111-1111-1111-1111-111111111111']);
    }

    public function test_accepts_any_token_during_rotation(): void
    {
        // Ротация без простоя: в конфиге два валидных ключа (старый + новый) одновременно.
        config(['services.qr_ingest.tokens' => ['old-key', 'new-key']]);

        // Старый ключ ещё валиден.
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), ['X-QR-Token' => 'old-key'])
            ->assertOk();

        // Новый ключ тоже валиден.
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), ['X-QR-Token' => 'new-key'])
            ->assertOk();
    }

    public function test_channel_is_closed_when_no_tokens_configured(): void
    {
        // Безопасный дефолт: ключи не сконфигурированы → канал закрыт даже с любым заголовком.
        config(['services.qr_ingest.tokens' => []]);

        $this->postJson('/api/v1/qrOrder/create', $this->contract(), ['X-QR-Token' => 'anything'])
            ->assertStatus(401);

        $this->assertDatabaseMissing('qr_orders', ['id' => '11111111-1111-1111-1111-111111111111']);
    }

    /** Перенаправляет канал qr_access в свежий тестовый файл и возвращает путь к нему. */
    private function redirectQrAccessLogToFile(): string
    {
        $logFile = storage_path('logs/qr_access_test.log');
        @unlink($logFile);
        config([
            'logging.channels.qr_access' => [
                'driver' => 'single',
                'path' => $logFile,
                'formatter' => \Monolog\Formatter\JsonFormatter::class,
            ],
        ]);

        return $logFile;
    }

    public function test_logs_accepted_connect_to_qr_access_channel(): void
    {
        $this->configureQrIngestToken();
        $logFile = $this->redirectQrAccessLogToFile();

        // Принятый коннект логируется в канал qr_access: connect.accepted + actor=qr + order_id.
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), $this->qrIngestHeaders())->assertOk();

        self::assertFileExists($logFile, 'qr_access лог-файл должен быть создан');
        $contents = (string) @file_get_contents($logFile);
        self::assertStringContainsString('connect.accepted', $contents);
        self::assertStringContainsString('"actor":"qr"', $contents);
        self::assertStringContainsString('"sub":"qr-service"', $contents);
        self::assertStringContainsString('11111111-1111-1111-1111-111111111111', $contents, 'order_id для корреляции');
        @unlink($logFile);
    }

    public function test_logs_rejected_connect_with_reason(): void
    {
        $this->configureQrIngestToken();
        $logFile = $this->redirectQrAccessLogToFile();

        // Отклонённый коннект (неверный ключ) тоже логируется — видны попытки взлома, reason=bad_token.
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), ['X-QR-Token' => 'wrong-key'])
            ->assertStatus(401);

        self::assertFileExists($logFile, 'qr_access лог-файл должен быть создан');
        $contents = (string) @file_get_contents($logFile);
        self::assertStringContainsString('connect.rejected', $contents);
        self::assertStringContainsString('"reason":"bad_token"', $contents);
        self::assertStringContainsString('"actor":"qr"', $contents);
        @unlink($logFile);
    }

    public function test_logs_accepted_with_non_string_order_id_safely(): void
    {
        $this->configureQrIngestToken();
        $logFile = $this->redirectQrAccessLogToFile();

        // order_id не строка (массив) — приём упадёт на валидации (422), но коннект всё равно
        // принят по ключу и залогирован: order_id безопасно становится null, лог не падает.
        $contract = $this->contract();
        $contract['order_id'] = ['nested' => 'oops'];
        $this->postJson('/api/v1/qrOrder/create', $contract, $this->qrIngestHeaders());

        $contents = (string) @file_get_contents($logFile);
        self::assertStringContainsString('connect.accepted', $contents);
        self::assertStringContainsString('"order_id":null', $contents);
        @unlink($logFile);
    }

    public function test_qr_token_is_never_written_to_logs(): void
    {
        $secret = 'super-secret-key-must-not-leak';
        config(['services.qr_ingest.tokens' => [$secret]]);
        $logFile = $this->redirectQrAccessLogToFile();

        // accepted (верный ключ) + rejected (неверный ключ) — сам ключ не должен попасть в лог.
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), ['X-QR-Token' => $secret])->assertOk();
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), ['X-QR-Token' => 'bad-'.$secret])->assertStatus(401);

        $contents = (string) @file_get_contents($logFile);
        self::assertNotSame('', $contents, 'qr_access лог должен содержать записи коннектов');
        self::assertStringNotContainsString($secret, $contents, 'Ключ X-QR-Token НЕ должен попадать в лог');
        @unlink($logFile);
    }
}
