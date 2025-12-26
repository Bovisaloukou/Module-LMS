<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    public function __construct(
        private StripePaymentService $stripePaymentService
    ) {}

    public function enroll(User $student, Course $course): array
    {
        if ($this->isEnrolled($student, $course)) {
            throw new \RuntimeException('Already enrolled in this course.');
        }

        if ($course->is_free || $course->price <= 0) {
            return $this->enrollFree($student, $course);
        }

        return $this->enrollPaid($student, $course);
    }

    public function isEnrolled(User $student, Course $course): bool
    {
        return Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->exists();
    }

    private function enrollFree(User $student, Course $course): array
    {
        $enrollment = $this->findOrCreateEnrollment($student, $course, 0);

        return [
            'enrollment' => $enrollment->load('course'),
            'requires_payment' => false,
        ];
    }

    private function enrollPaid(User $student, Course $course): array
    {
        return DB::transaction(function () use ($student, $course) {
            $enrollment = $this->findOrCreateEnrollment($student, $course, $course->price);

            $paymentIntent = $this->stripePaymentService->createPaymentIntent(
                $enrollment,
                $student,
                $course
            );

            return [
                'enrollment' => $enrollment->load('course'),
                'requires_payment' => true,
                'client_secret' => $paymentIntent['client_secret'],
                'payment_intent_id' => $paymentIntent['id'],
            ];
        });
    }

    private function findOrCreateEnrollment(User $student, Course $course, float $price): Enrollment
    {
        $existing = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            $existing->update([
                'price_paid' => $price,
                'status' => EnrollmentStatus::Active,
                'enrolled_at' => now(),
                'completed_at' => null,
            ]);

            return $existing->fresh();
        }

        return Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'price_paid' => $price,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ])->fresh();
    }

    public function confirmPayment(string $paymentIntentId): Enrollment
    {
        return $this->stripePaymentService->confirmPayment($paymentIntentId);
    }
}
