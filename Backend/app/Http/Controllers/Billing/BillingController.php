<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use Tickets\Billing\Application\Billing;

class BillingController extends Controller
{
    public function __construct(
        private Billing $billing
    )
    {
    }


    /**
     * @throws Throwable
     */
    public function webHook(Request $request): JsonResponse
    {

        Log::debug('Billing', $request->toArray());
        $data = $request->toArray();
        if(!empty($data['data']['metadata']['order_id'])) {
            $this->billing->webHook(
                $data['data']['metadata']['order_id'],
                $data['type'],
                $data['data']['receipts'][0]['link_to_receipt']
            );
        }

        return response()->json(["status" => 0]);
    }
}
