<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tickets\Billing\Application\Billing;

class BillingController extends Controller
{
    public function __construct(
        private Billing $billing
    )
    {
    }


    public function webHook(Request $request): JsonResponse
    {
        dd($request->toArray());
    }
}
