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

        $user = User::where('email', $data['user_email'])->first();
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

    public function getFestival(): string
    {
        return json_encode([
            'festival_list' => [
                env('UUID_FESTIVAL') => [
                    'name' => 'Систо 2024',
                    'date_start' => '2024-02-16 13:47:53',
                    'date_end' => '2024-05-16 13:47:53',
                ],
                env('UUID_SECOND_FESTIVAL') => [
                    'name' => 'Систо-Осень 2024',
                    'date_start' => '2024-05-16 13:47:53',
                    'date_end' => '2024-08-16 13:47:53',
                ],
            ],
        ]);
    }
}
