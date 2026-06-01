<?php

declare(strict_types=1);

namespace App\Http\Controllers\Option;

use App\Http\Controllers\Controller;
use App\Http\Requests\Option\OptionCreateRequest;
use App\Http\Requests\Option\OptionEditRequest;
use App\Http\Requests\Option\OptionGetListRequest;
use DomainException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Nette\Utils\JsonException;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Option\Application\GetList\OptionGetListQuery;
use Tickets\Option\Application\OptionApplication;
use Tickets\Option\Dto\OptionDto;
use Tickets\Option\Dto\OptionForTicketTypeView;
use Tickets\Option\Dto\OptionTicketTypeBindingDto;

/**
 * REST-контроллер опций к билетам (v2.6.0).
 *
 * Read-операции (getList/getItem/getActiveOptionsForTicketType) —
 * публичные (нужны фронту формы покупки). Write-операции —
 * только админ (защита от дурака на уровне роутов).
 *
 * См. `.claude/specs/ticket-options.md`.
 */
class OptionController extends Controller
{
    public function getList(
        OptionGetListRequest $request,
        OptionApplication $application,
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $orderBy = Order::fromState($validated['orderBy'] ?? []);
        } catch (InvalidArgumentException) {
            $orderBy = Order::none();
        }

        $collection = $application->getList(
            new OptionGetListQuery($validated['filter'] ?? [], $orderBy),
        )->getCollection();

        return response()->json([
            'success' => true,
            'list' => $collection->map(fn (OptionDto $dto) => $dto->toArray())->values()->all(),
        ]);
    }

    /**
     * @throws JsonException
     */
    public function getItem(
        string $id,
        OptionApplication $application,
    ): JsonResponse {
        try {
            $option = $application->getItem(new Uuid($id));
            $bindings = $application->getTicketTypeBindings(new Uuid($id));

            return response()->json([
                'success' => true,
                'item' => array_merge($option->toArray(), [
                    'bindings' => array_map(
                        static fn (OptionTicketTypeBindingDto $b) => [
                            'ticket_type_id' => $b->getTicketTypeId()->value(),
                            'description' => $b->getDescription(),
                        ],
                        $bindings
                    ),
                ]),
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
     * Read-модель: активные опции конкретного типа билета (для формы покупки).
     */
    public function getActiveForTicketType(
        string $ticketTypeId,
        OptionApplication $application,
    ): JsonResponse {
        try {
            $list = $application->getActiveOptionsForTicketType(new Uuid($ticketTypeId));

            return response()->json([
                'success' => true,
                'list' => array_map(
                    static fn (OptionForTicketTypeView $view) => $view->toArray(),
                    $list
                ),
            ]);
        } catch (InvalidArgumentException) {
            return response()->json([
                'success' => false,
                'message' => 'Некорректный идентификатор типа билета',
            ], 400);
        }
    }

    /**
     * @throws Throwable
     */
    public function create(
        OptionCreateRequest $request,
        OptionApplication $application,
    ): JsonResponse {
        try {
            $validated = $request->validated()['data'];
            $bindings = array_map(
                static fn (array $row) => OptionTicketTypeBindingDto::fromState($row),
                $validated['bindings'] ?? []
            );

            $data = OptionDto::fromState($validated);

            return response()->json([
                'success' => $application->create($data, $bindings),
                'item' => $application->getItem($data->getId())->toArray(),
                'message' => 'Опция создана',
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
        OptionEditRequest $request,
        OptionApplication $application,
    ): JsonResponse {
        try {
            $payload = $request->validated()['data'];
            $payload['id'] = $id;

            $bindings = isset($payload['bindings'])
                ? array_map(
                    static fn (array $row) => OptionTicketTypeBindingDto::fromState($row),
                    $payload['bindings']
                )
                : null;

            return response()->json([
                'success' => $application->edit(
                    new Uuid($id),
                    OptionDto::fromState($payload),
                    $bindings
                ),
                'item' => $application->getItem(new Uuid($id))->toArray(),
                'message' => 'Опция отредактирована',
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
        OptionApplication $application,
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
