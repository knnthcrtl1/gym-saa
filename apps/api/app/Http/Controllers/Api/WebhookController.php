<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function paymongo(Request $request)
    {
        $result = $this->paymentService->handlePayMongoWebhook(
            $request->getContent(),
            $request->headers->all(),
        );

        return response()->json([
            'message' => $result['message'],
        ], $result['accepted'] ? 200 : 400);
    }
}