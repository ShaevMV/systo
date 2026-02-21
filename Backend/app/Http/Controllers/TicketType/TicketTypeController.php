<?php

declare(strict_types=1);

namespace App\Http\Controllers\TicketType;

use App\Http\Controllers\Controller;
use Doctrine\DBAL\Types\Type;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nette\Utils\JsonException;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\TicketType\Application\GetItem\TicketTypeGetListQuery;
use Tickets\TicketType\Application\TicketTypeApplication;
use Tickets\TypesOfPayment\Application\GetList\TypesOfPaymentGetListQuery;
use Tickets\TypesOfPayment\Application\TypesOfPaymentApplication;
use Tickets\TypesOfPayment\Dto\TypesOfPaymentDto;

class TicketTypeController extends Controller
{
    public function getList(
        Request $request,
        TicketTypeApplication $application,
    ): JsonResponse
    {
        return response()->json([
            'success' => true,
            'list' => $application->getList(
                TicketTypeGetListQuery::fromState($request->toArray()['filter'])
            )->getCollection()
                ->toArray(),
        ]);
    }

    public function getItem(
        string $id,
        TypesOfPaymentApplication $application,
    ): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'item' => $application->getItem(new Uuid($id))->toArray(),
            ]);
        } catch (\DomainException $exception) {
            return response()->json([
                'success' => false,
                'massage' => $exception->getMessage(),
            ]);
        }

    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function edit(
        string $id,
        Request $request,
        TypesOfPaymentApplication $application,
    ): JsonResponse
    {
        try {
            return response()->json([
                'success' => $application->edit(
                    new Uuid($id),
                    TypesOfPaymentDto::fromState($request->toArray()['data'])
                ),
                'item' => $application->getItem(new Uuid($id))->toArray()
            ]);
        } catch (\DomainException $exception) {
            return response()->json([
                'success' => false,
                'massage' => $exception->getMessage()
            ]);
        }

    }

    /**
     * @throws Throwable
     */
    public function create(
        Request $request,
        TypesOfPaymentApplication $application,
    ): JsonResponse
    {
        $paymentDto = TypesOfPaymentDto::fromState($request->toArray()['data']);

        return response()->json([
            'success' => $application->create($paymentDto),
            'item' => $application->getItem($paymentDto->getId())->toArray()
        ]);
    }

    /**
     * @throws Throwable
     */
    public function delete(
        string $id,
        TypesOfPaymentApplication $application,
    ): JsonResponse
    {
        return response()->json([
            'success' => $application->delete(new Uuid($id)),
        ]);
    }
}
