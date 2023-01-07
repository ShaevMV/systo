<?php

declare(strict_types=1);

namespace App\Http\Controllers\TicketsOrder;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCommentForOrderRequest;
use Auth;
use Illuminate\Http\JsonResponse;
use Throwable;
use Tickets\Order\OrderTicket\Application\AddComment\AddComment;
use Tickets\Shared\Domain\ValueObject\Uuid;

class Comment extends Controller
{
    public function __construct(
        private AddComment $addComment,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function addComment(CreateCommentForOrderRequest $commentForOrderRequest): JsonResponse
    {
        $this->addComment->send(
            new Uuid($commentForOrderRequest->orderId),
            new Uuid(Auth::id()),
            $commentForOrderRequest->message,
        );

        return response()->json([
            'success' => true,
            'created_at' => date("Y-m-d H:i:s")
        ]);
    }
}
