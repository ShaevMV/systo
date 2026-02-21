<?php

declare(strict_types=1);

namespace App\Http\Controllers\TypesOfPayment;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tickets\TypesOfPayment\Application\GetList\TypesOfPaymentGetListQuery;
use Tickets\TypesOfPayment\Application\TypesOfPaymentApplication;

class TypesOfPaymentController extends Controller
{
    public function getList(
        Request $request,
        TypesOfPaymentApplication $application,
    ): JsonResponse
    {
        return response()->json([
            'success' => true,
            'list' => $application->getList(
                TypesOfPaymentGetListQuery::fromState($request->toArray())
            )->getTypesOfPaymentList()->toArray(),
        ]);
    }
}
