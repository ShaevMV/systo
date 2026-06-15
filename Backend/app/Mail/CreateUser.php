<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\RendersDbTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CreateUser extends Mailable
{
    use Queueable, SerializesModels, RendersDbTemplate;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        private string $login,
        private string $password,
    ) {
        $this->subject('Solar Systo Togathering');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        // Активный DB-шаблон (Mustache) или fallback на blade email.newUser.
        return $this->renderDbOrView('newUser', [
            'login' => $this->login,
            'password' => $this->password,
        ]);
    }
}
