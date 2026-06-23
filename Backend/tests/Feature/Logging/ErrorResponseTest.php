<?php

declare(strict_types=1);

namespace Tests\Feature\Logging;

use App\Http\Responses\ErrorResponse;
use Illuminate\Support\Facades\Log;
use Shared\Domain\DomainError;
use Tests\TestCase;

/**
 * Единый ответ об ошибке: НЕ отдаёт file/line/сырой getMessage (TD-29, 152-ФЗ),
 * полную диагностику пишет в канал `structured`.
 */
class ErrorResponseTest extends TestCase
{
    public function test_generic_throwable_has_no_leak_and_internal_code(): void
    {
        Log::shouldReceive('channel')->with('structured')->andReturnSelf();
        Log::shouldReceive('error')->once();

        $response = ErrorResponse::fromThrowable(
            new \RuntimeException('SQLSTATE secret at /var/www/app/Some.php line 42'),
        );

        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertSame('internal_error', $data['code']);
        // Утечки устранены.
        $this->assertArrayNotHasKey('file', $data);
        $this->assertArrayNotHasKey('link', $data);
        $this->assertStringNotContainsString('/var/www', $data['message']);
        $this->assertStringNotContainsString('SQLSTATE', $data['message']);
    }

    public function test_domain_error_keeps_its_code_and_safe_message(): void
    {
        Log::shouldReceive('channel')->with('structured')->andReturnSelf();
        Log::shouldReceive('error')->once();

        $domain = new class () extends DomainError {
            public function errorCode(): string
            {
                return 'promo_code_empty';
            }

            protected function errorMessage(): string
            {
                return 'Промокод пустой';
            }
        };

        $data = ErrorResponse::fromThrowable($domain)->getData(true);

        $this->assertFalse($data['success']);
        $this->assertSame('promo_code_empty', $data['code']);
        $this->assertSame('Промокод пустой', $data['message']);
        $this->assertArrayNotHasKey('file', $data);
    }
}
