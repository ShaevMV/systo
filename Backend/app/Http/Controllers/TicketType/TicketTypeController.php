<?php

declare(strict_types=1);

namespace App\Http\Controllers\TicketType;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nette\Utils\JsonException;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\TicketType\Application\GetList\TicketTypeGetListFilter;
use Tickets\TicketType\Application\GetList\TicketTypeGetListQuery;
use Tickets\TicketType\Application\TicketTypeApplication;
use Tickets\TicketType\Dto\TicketTypeDto;
use Tickets\Template\Service\TemplateService;

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
                new TicketTypeGetListQuery(
                    TicketTypeGetListFilter::fromState($request->toArray()['filter']),
                    Order::fromState($request->toArray()['orderBy'])
                ),
            )->getCollection()
                ->toArray(),
        ]);
    }

    /**
     * @throws JsonException
     */
    public function getItem(
        string $id,
        TicketTypeApplication $application,
    ): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'item' => $application->getItem(new Uuid($id))->toArray(),
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'success' => false,
                'massage' => $exception->getMessage(),
            ]);
        }
    }

    public function getBlade(
        TemplateService $templateService,
    ): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'list' => [
                    'email' => $templateService->getList('views/email')->toArray(),
                    'pdf' => $templateService->getList('views')->toArray(),
                ],
            ]);
        } catch (DomainException $exception) {
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
        TicketTypeApplication $application,
    ): JsonResponse
    {
        try {
            return response()->json([
                'success' => $application->edit(
                    new Uuid($id),
                    TicketTypeDto::fromState($request->toArray()['data'])
                ),
                'item' => $application->getItem(new Uuid($id))->toArray(),
                'message' => 'Тип билета отредактирован'
            ]);
        } catch (DomainException $exception) {
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
        TicketTypeApplication $application,
    ): JsonResponse
    {
        $data = TicketTypeDto::fromState($request->toArray()['data']);

        return response()->json([
            'success' => $application->create($data),
            'item' => $application->getItem($data->getId())->toArray(),
            'message' => 'Тип билета создан'
        ]);
    }

    /**
     * @throws Throwable
     */
    public function delete(
        string $id,
        TicketTypeApplication $application,
    ): JsonResponse
    {
        return response()->json([
            'success' => $application->delete(new Uuid($id)),
        ]);
    }
}
