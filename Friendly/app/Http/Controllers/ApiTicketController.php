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
}
