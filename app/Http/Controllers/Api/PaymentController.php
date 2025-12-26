<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Payments
 *
 * APIs for payment management.
 */
class PaymentController extends Controller
{
    public function __construct(
        private StripePaymentService $stripePaymentService
    ) {}

    /**
     * List My Payments
     *
     * Get all payments for the authenticated user.
     *
     * @authenticated
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $payments = Payment::where('student_id', $request->user()->id)
            ->with(['course'])
            ->latest()
            ->paginate(15);

        return PaymentResource::collection($payments);
    }

    /**
     * Stripe Webhook
     *
     * Handle Stripe webhook events (payment_intent.succeeded, payment_intent.payment_failed).
     *
     * @unauthenticated
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        try {
            $this->stripePaymentService->handleWebhook($payload, $signature);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['status' => 'ok']);
    }
}
