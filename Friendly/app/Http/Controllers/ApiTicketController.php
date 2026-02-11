<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\Bot;
use App\Models\User;
use App\Services\ApiTicketService;
use App\Services\DTO\CreateApiTicketDTO;
use Illuminate\Http\Request;
use Throwable;

class ApiTicketController extends Controller
{
    public function __construct(
        private ApiTicketService $ticketService
    )
    {
    }


    /**
     * @throws Throwable
     */
    public function insert(Request $request): string
    {
        $data = json_decode($request->getContent(), true);

        $user = User::where('email', Bot::getUserEmailByToken(
            $request->headers->get('auth-token')
        ))->first();
        if (null === $user) {
            return json_encode([
                'success' => false,
                'error' => 'UserNotFound',
            ]);
        }
        $createApiTicketDTO = CreateApiTicketDTO::fromState($data, $user->id);

        return json_encode([
            'success' => $this->ticketService->create($createApiTicketDTO),
            'error' => null,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function getList(): string
    {
        return json_encode([
            'festival_list' => [
                "9d679bcf-b438-4ddb-ac04-023fa9bff4b0" => [
                    "name" => "Тест 3025",
                    "date_start" => "2025-09-10 00:00:00",
                    "date_end" => "2025-09-15 00:00:00"
                ],
                "9d679bcf-b438-4ddb-ac04-023fa9bff4b8" => [
                    "name" => "Систо-Осень 2025",
                    "date_start" => "2025-08-01 00:00:00",
                    "date_end" => "2025-09-15 00:00:00"
                ]
            ]
        ]);
    }
}
