<?php

namespace App\Mail;

use App\Mail\Concerns\RendersDbTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Письмо гостю «анкета одобрена».
 *
 * Шлётся при одобрении анкеты администратором (см. Questionnaire::toApprove →
 * ProcessQuestionnaireApprovedNotification). Несёт ссылку-приглашение (оплата оргвзноса).
 * Рендер: активный DB-шаблон (Mustache, slug questionnaireApproved) или fallback на
 * blade email.questionnaireApproved.
 */
class QuestionnaireApproved extends Mailable
{
    use Queueable, SerializesModels, RendersDbTemplate;

    public function __construct(
        private string $link,
    ) {
    }

    public function build(): static
    {
        $this->subject('Твоя анкета одобрена — Solar Systo Togathering');

        return $this->renderDbOrView('questionnaireApproved', [
            'link' => $this->link,
        ]);
    }
}
