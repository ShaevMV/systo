<?php

declare(strict_types=1);

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Location\Application\GetList\LocationGetListFilter;
use Tickets\Location\Application\GetList\LocationGetListQuery;
use Tickets\Location\Application\LocationApplication;
use Tickets\Location\Dto\LocationDto;

class LocationController extends Controller
{
    public function getList(
        Request             $request,
        LocationApplication $application,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'list'    => $application->getList(
                new LocationGetListQuery(
                    LocationGetListFilter::fromState($request->toArray()['filter'] ?? []),
                    Order::fromState($request->toArray()['orderBy'] ?? []),
                )
            )->getCollection()->toArray(),
        ]);
    }

    public function getItem(
        string              $id,
        LocationApplication $application,
    ): JsonResponse {
        try {
            return response()->json([
                'success' => true,
                'item'    => $application->getItem(new Uuid($id))->toArray(),
            ]);
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
        Request             $request,
        LocationApplication $application,
    ): JsonResponse {
        $data = LocationDto::fromState($request->toArray()['data']);

        return response()->json([
            'success' => $application->create($data),
            'item'    => $application->getItem($data->getId())->toArray(),
            'message' => 'Локация создана',
        ]);
    }

    /**
     * @throws Throwable
     */
    public function edit(
        string              $id,
        Request             $request,
        LocationApplication $application,
    ): JsonResponse {
        try {
            return response()->json([
                'success' => $application->edit(
                    new Uuid($id),
                    LocationDto::fromState($request->toArray()['data']),
                ),
                'item'    => $application->getItem(new Uuid($id))->toArray(),
                'message' => 'Локация отредактирована',
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
     */
    public function delete(
        string              $id,
        LocationApplication $application,
    ): JsonResponse {
        return response()->json([
            'success' => $application->delete(new Uuid($id)),
        ]);
    }
}
