<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createPaymentIntent(Enrollment $enrollment, User $student, Course $course): array
    {
        $paymentIntent = PaymentIntent::create([
            'amount' => (int) ($course->price * 100),
            'currency' => 'usd',
            'metadata' => [
                'enrollment_id' => $enrollment->id,
                'course_id' => $course->id,
                'student_id' => $student->id,
            ],
        ]);

        Payment::create([
            'enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'amount' => $course->price,
            'currency' => 'usd',
            'status' => PaymentStatus::Pending,
        ]);

        return [
            'id' => $paymentIntent->id,
            'client_secret' => $paymentIntent->client_secret,
        ];
    }

    public function handleWebhook(string $payload, string $signature): void
    {
        $event = Webhook::constructEvent(
            $payload,
            $signature,
            config('services.stripe.webhook_secret')
        );

        match ($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            default => null,
        };
    }

    public function confirmPayment(string $paymentIntentId): Enrollment
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->firstOrFail();

        $payment->update([
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
        ]);

        return $payment->enrollment;
    }

    public function refund(Payment $payment): Payment
    {
        if ($payment->status !== PaymentStatus::Succeeded) {
            throw new \RuntimeException('Can only refund succeeded payments.');
        }

        if ($payment->stripe_payment_intent_id) {
            \Stripe\Refund::create([
                'payment_intent' => $payment->stripe_payment_intent_id,
            ]);
        }

        $payment->markAsRefunded();

        $payment->enrollment->update([
            'status' => \App\Enums\EnrollmentStatus::Refunded,
        ]);

        return $payment->fresh();
    }

    private function handlePaymentSucceeded(object $paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (! $payment) {
            return;
        }

        $payment->update([
            'status' => PaymentStatus::Succeeded,
            'stripe_charge_id' => $paymentIntent->latest_charge ?? null,
            'payment_method' => $paymentIntent->payment_method_types[0] ?? null,
            'paid_at' => now(),
        ]);
    }

    private function handlePaymentFailed(object $paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (! $payment) {
            return;
        }

        $payment->markAsFailed();
    }
}
