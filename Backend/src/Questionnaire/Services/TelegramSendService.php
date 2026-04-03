<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramSendService
{
    public static function send($username)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            'http://77.222.60.58:8000',
            [
                'username' => $username,
                'token' => 'sy-HShs-0d7a-psdM-19Bw',
            ]
        );
        Log::info($username);
        if ($response->getStatusCode() !== 200) {
            throw new \DomainException('не отправлен ' . $username . ' ответ ' . $response->getBody()->getContents() , $response->getStatusCode());
        }

        Log::info($response->getBody()->getContents());
    }
}