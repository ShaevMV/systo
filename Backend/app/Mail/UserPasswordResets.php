<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\RendersDbTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserPasswordResets extends Mailable
{
    use Queueable, SerializesModels, RendersDbTemplate;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        private string $activationLink,
    ){
        $this->subject('Письмо с восстановлением пароля');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        // Активный DB-шаблон (Mustache) или fallback на blade email.passwordResets.
        return $this->renderDbOrView('passwordResets', [
            'link' => $this->activationLink
        ]);
    }
}
