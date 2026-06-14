<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Tickets\User\Account\Helpers\AccountRoleHelper;

/**
 * Выпускает S2S-токен Sanctum для канала qr→org.
 *
 * Токен привязан к служебному аккаунту (role qr_service, не человек) и имеет единственный
 * scope (ability) "qr:ingest" — его проверяет middleware abilities на /qrOrder/create и
 * /qrOrder/changeStatus. Plaintext токена показывается ОДИН раз: его кладут в .env qr-сервера
 * и шлют заголовком `Authorization: Bearer <token>`. В БД хранится только sha256-хеш.
 *
 * Это ops-инструмент (как key:generate / jwt:secret), а не часть request-потока,
 * поэтому работа с моделью здесь допустима (Sanctum привязывает токен к Eloquent-модели).
 */
class QrIssueServiceToken extends Command
{
    protected $signature = 'qr:issue-token {--revoke-old : Отозвать все ранее выпущенные токены сервис-аккаунта}';

    protected $description = 'Выпустить S2S-токен Sanctum для приёма заказов с витрины qr (ability: qr:ingest)';

    /** Email служебного аккаунта витрины qr (не используется для входа — пароль случайный). */
    private const SERVICE_EMAIL = 'qr-service@system.local';

    /** Единственный scope токена: право принимать заказы и менять их статус. */
    private const ABILITY = 'qr:ingest';

    public function handle(): int
    {
        // Служебный аккаунт создаётся один раз и переиспользуется (id ставит HasUuid).
        $serviceAccount = User::firstOrCreate(
            ['email' => self::SERVICE_EMAIL],
            [
                'name' => 'QR Service',
                'role' => AccountRoleHelper::qr_service,
                'password' => bcrypt(Str::random(64)),
            ],
        );

        if ($this->option('revoke-old')) {
            $revoked = $serviceAccount->tokens()->delete();
            $this->warn(sprintf('Отозвано старых токенов: %d', $revoked));
        }

        $token = $serviceAccount->createToken('qr-s2s', [self::ABILITY])->plainTextToken;

        $this->newLine();
        $this->info('Токен выпущен. Покажется ОДИН раз — положи в .env qr-сервера.');
        $this->line('Заголовок запроса:  Authorization: Bearer <token>');
        $this->newLine();
        $this->line($token);
        $this->newLine();

        return self::SUCCESS;
    }
}
