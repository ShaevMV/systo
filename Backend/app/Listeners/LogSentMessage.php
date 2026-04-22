<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class LogSentMessage
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        // $event->sent — это Illuminate\Mail\SentMessage
        /** @var \Illuminate\Mail\SentMessage $sentMessage */
        $sentMessage = $event->sent;

        // Получаем оригинальный Symfony SentMessage
        $symfonySentMessage = $sentMessage->getSymfonySentMessage();

        // Получаем уникальный ID письма (генерируется транспортом)
        $messageId = $symfonySentMessage->getMessageId();

        // Получаем получателей из конверта
        $recipients = $symfonySentMessage->getEnvelope()->getRecipients();

        // Логируем факт успешной отправки
        Log::info('Письмо успешно отправлено', [
            'message_id' => $messageId,
           // 'recipients' => implode(', ', $recipients ?? []),
            'subject' => $event->data['subject'] ?? null,
        ]);

        // Здесь можно сохранить ID в БД для отслеживания
        // DB::table('email_log')->update(['message_id' => $messageId]);
    }
}
