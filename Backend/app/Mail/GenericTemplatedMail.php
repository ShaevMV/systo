<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\RendersDbTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Универсальное письмо: рендерит произвольный slug с переданными vars (Mustache из БД с
 * fallback на blade — как у остальных писем). Для S2S-канала уведомлений от витрины qr (Ф4):
 * регистрация/сброс пароля и прочие не-заказные письма, где витрина шлёт данные напрямую.
 *
 * Сериализуемо (slug/subject — строки, vars — массив) → переживает очередь и повторную отправку.
 */
class GenericTemplatedMail extends Mailable
{
    use Queueable;
    use SerializesModels;
    use RendersDbTemplate;

    /**
     * @param array<string, mixed> $vars
     */
    public function __construct(
        private string $slug,
        private string $subjectLine,
        private array $vars = [],
    ) {
    }

    public function build(): static
    {
        $this->subject($this->subjectLine);

        return $this->renderDbOrView($this->slug, $this->vars);
    }
}
