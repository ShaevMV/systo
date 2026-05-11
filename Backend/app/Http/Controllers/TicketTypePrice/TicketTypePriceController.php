<?php

declare(strict_types=1);

namespace App\Http\Controllers\TicketTypePrice;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketTypePrice\TicketTypePriceCreateRequest;
use App\Http\Requests\TicketTypePrice\TicketTypePriceEditRequest;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Nette\Utils\JsonException;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\TicketTypePrice\Application\GetList\TicketTypePriceGetListQuery;
use Tickets\TicketTypePrice\Application\TicketTypePriceApplication;
use Tickets\TicketTypePrice\Dto\TicketTypePriceDto;

class TicketTypePriceController extends Controller
{
    public function getList(
        Request $request,
        TicketTypePriceApplication $application,
    ): JsonResponse {
        $collection = $application->getList(
            new TicketTypePriceGetListQuery(
                $request->toArray()['filter'] ?? [],
                Order::fromState($request->toArray()['orderBy'] ?? [])
            ),
        )->getCollection();

        return response()->json([
            'success' => true,
            'list' => $collection->map(fn (TicketTypePriceDto $dto) => $dto->toArray())->values()->all(),
        ]);
    }

    /**
     * @throws JsonException
     */
    public function getItem(
        string $id,
        TicketTypePriceApplication $application,
    ): JsonResponse {
        try {
            return response()->json([
                'success' => true,
                'item' => $application->getItem(new Uuid($id))->toArray(),
            ]);
        } catch (InvalidArgumentException $exception) {
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
        TicketTypePriceCreateRequest $request,
        TicketTypePriceApplication $application,
    ): JsonResponse {
        try {
            $data = TicketTypePriceDto::fromState($request->validated()['data']);

            return response()->json([
                'success' => $application->create($data),
                'item' => $application->getItem($data->getId())->toArray(),
                'message' => 'Волна цены создана',
            ]);
        } catch (InvalidArgumentException $exception) {
            // Защита от некорректного UUID в data.id (FormRequest уже валидирует,
            // но оставляем guard для случаев минуя валидацию)
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
        TicketTypePriceEditRequest $request,
        TicketTypePriceApplication $application,
    ): JsonResponse {
        try {
            $payload = $request->validated()['data'];
            // Гарантируем, что id из URL совпадает с DTO
            $payload['id'] = $id;

            return response()->json([
                'success' => $application->edit(
                    new Uuid($id),
                    TicketTypePriceDto::fromState($payload)
                ),
                'item' => $application->getItem(new Uuid($id))->toArray(),
                'message' => 'Волна цены отредактирована',
            ]);
        } catch (InvalidArgumentException $exception) {
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
        TicketTypePriceApplication $application,
    ): JsonResponse {
        try {
            return response()->json([
                'success' => $application->delete(new Uuid($id)),
            ]);
        } catch (InvalidArgumentException $exception) {
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
