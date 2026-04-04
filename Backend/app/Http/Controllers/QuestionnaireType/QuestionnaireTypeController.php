<?php

declare(strict_types=1);

namespace App\Http\Controllers\QuestionnaireType;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nette\Utils\JsonException;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\QuestionnaireType\Application\QuestionnaireTypeApplication;
use Tickets\QuestionnaireType\Application\GetList\QuestionnaireTypeGetListQuery;
use Tickets\QuestionnaireType\Dto\QuestionnaireTypeDto;

class QuestionnaireTypeController extends Controller
{
    public function getList(
        Request $request,
        QuestionnaireTypeApplication $application,
    ): JsonResponse
    {
        return response()->json([
            'success' => true,
            'list' => $application->getList(
                new QuestionnaireTypeGetListQuery(
                    $request->toArray()['filter'] ?? [],
                    Order::fromState($request->toArray()['orderBy'] ?? [])
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
        QuestionnaireTypeApplication $application,
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
                'message' => $exception->getMessage(),
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
        QuestionnaireTypeApplication $application,
    ): JsonResponse
    {
        try {
            return response()->json([
                'success' => $application->edit(
                    new Uuid($id),
                    QuestionnaireTypeDto::fromState($request->toArray()['data'])
                ),
                'item' => $application->getItem(new Uuid($id))->toArray(),
                'message' => 'Тип анкеты отредактирован'
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ]);
        }
    }

    /**
     * @throws Throwable
     */
    public function create(
        Request $request,
        QuestionnaireTypeApplication $application,
    ): JsonResponse
    {
        $data = QuestionnaireTypeDto::fromState($request->toArray()['data']);

        return response()->json([
            'success' => $application->create($data),
            'item' => $application->getItem($data->getId())->toArray(),
            'message' => 'Тип анкеты создан'
        ]);
    }

    /**
     * @throws Throwable
     */
    public function delete(
        string $id,
        QuestionnaireTypeApplication $application,
    ): JsonResponse
    {
        return response()->json([
            'success' => $application->delete(new Uuid($id)),
        ]);
    }
}
