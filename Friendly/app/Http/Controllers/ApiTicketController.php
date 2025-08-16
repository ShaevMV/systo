<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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

        $user = User::where('email', 'bot@telegram.com')->first();
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
                    "name" => "Тест",
                    "date_start" => "2025-01-16 13:47:53",
                    "date_end" => "2025-12-16 13:47:53"
                ],
                "9d679bcf-b438-4ddb-ac04-023fa9bff4b7" => [
                    "name" => "Систо-Осень 2025",
                    "date_start" => "2025-01-28 00:00:00",
                    "date_end" => "2025-05-26 00:00:00"
                ]
            ]
        ]);
    }
}
