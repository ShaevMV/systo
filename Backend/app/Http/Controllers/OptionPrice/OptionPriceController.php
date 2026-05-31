<?php

declare(strict_types=1);

namespace App\Http\Controllers\OptionPrice;

use App\Http\Controllers\Controller;
use App\Http\Requests\OptionPrice\OptionPriceCreateRequest;
use App\Http\Requests\OptionPrice\OptionPriceEditRequest;
use App\Http\Requests\OptionPrice\OptionPriceGetListRequest;
use DomainException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Nette\Utils\JsonException;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\OptionPrice\Application\GetList\OptionPriceGetListQuery;
use Tickets\OptionPrice\Application\OptionPriceApplication;
use Tickets\OptionPrice\Dto\OptionPriceDto;

/**
 * REST-контроллер волн цен опций (v2.6.0).
 *
 * Полный аналог `TicketTypePriceController` — управляет таблицей
 * `option_price`. Read публично, write только админ.
 */
class OptionPriceController extends Controller
{
    public function getList(
        OptionPriceGetListRequest $request,
        OptionPriceApplication $application,
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $orderBy = Order::fromState($validated['orderBy'] ?? []);
        } catch (InvalidArgumentException) {
            $orderBy = Order::none();
        }

        $collection = $application->getList(
            new OptionPriceGetListQuery($validated['filter'], $orderBy),
        )->getCollection();

        return response()->json([
            'success' => true,
            'list' => $collection->map(fn (OptionPriceDto $dto) => $dto->toArray())->values()->all(),
        ]);
    }

    /**
     * @throws JsonException
     */
    public function getItem(
        string $id,
        OptionPriceApplication $application,
    ): JsonResponse {
        try {
            return response()->json([
                'success' => true,
                'item' => $application->getItem(new Uuid($id))->toArray(),
            ]);
        } catch (InvalidArgumentException) {
            return response()->json([
                'success' => false,
                'message' => 'Некорректный идентификатор',
            ], 400);
        } catch (DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 404);
        }
    }

    /**
     * @throws Throwable
     */
    public function create(
        OptionPriceCreateRequest $request,
        OptionPriceApplication $application,
    ): JsonResponse {
        try {
            $data = OptionPriceDto::fromState($request->validated()['data']);

            return response()->json([
                'success' => $application->create($data),
                'item' => $application->getItem($data->getId())->toArray(),
                'message' => 'Волна цены опции создана',
            ]);
        } catch (InvalidArgumentException) {
            return response()->json([
                'success' => false,
                'message' => 'Некорректный идентификатор',
            ], 400);
        }
    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function edit(
        string $id,
        OptionPriceEditRequest $request,
        OptionPriceApplication $application,
    ): JsonResponse {
        try {
            $payload = $request->validated()['data'];
            $payload['id'] = $id;

            return response()->json([
                'success' => $application->edit(
                    new Uuid($id),
                    OptionPriceDto::fromState($payload)
                ),
                'item' => $application->getItem(new Uuid($id))->toArray(),
                'message' => 'Волна цены опции отредактирована',
            ]);
        } catch (InvalidArgumentException) {
            return response()->json([
                'success' => false,
                'message' => 'Некорректный идентификатор',
            ], 400);
        } catch (DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 404);
        }
    }

    /**
     * @throws Throwable
     */
    public function delete(
        string $id,
        OptionPriceApplication $application,
    ): JsonResponse {
        try {
            return response()->json([
                'success' => $application->delete(new Uuid($id)),
            ]);
        } catch (InvalidArgumentException) {
            return response()->json([
                'success' => false,
                'message' => 'Некорректный идентификатор',
            ], 400);
        } catch (DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 404);
        }
    }
}
