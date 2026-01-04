<?php

namespace Tests\Feature\Api\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripePaymentService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $instructor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        $this->instructor = User::factory()->create();
        $this->instructor->assignRole('instructor');
    }

    public function test_student_can_list_payments(): void
    {
        Sanctum::actingAs($this->student);

        $course = Course::factory()->published()->create(['instructor_id' => $this->instructor->id]);

        $enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'price_paid' => 49.99,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        Payment::create([
            'enrollment_id' => $enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'stripe_payment_intent_id' => 'pi_test_123',
            'amount' => 49.99,
            'currency' => 'usd',
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
        ]);

        $response = $this->getJson('/api/payments');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount', '49.99')
            ->assertJsonPath('data.0.status', 'succeeded');
    }

    public function test_payment_list_only_shows_own_payments(): void
    {
        Sanctum::actingAs($this->student);

        $course = Course::factory()->published()->create(['instructor_id' => $this->instructor->id]);

        // Other student's payment
        $otherStudent = User::factory()->create();
        $enrollment = Enrollment::create([
            'student_id' => $otherStudent->id,
            'course_id' => $course->id,
            'price_paid' => 49.99,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        Payment::create([
            'enrollment_id' => $enrollment->id,
            'student_id' => $otherStudent->id,
            'course_id' => $course->id,
            'stripe_payment_intent_id' => 'pi_other_123',
            'amount' => 49.99,
            'currency' => 'usd',
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
        ]);

        $response = $this->getJson('/api/payments');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_unauthenticated_cannot_list_payments(): void
    {
        $response = $this->getJson('/api/payments');

        $response->assertUnauthorized();
    }

    public function test_payment_confirm_updates_status(): void
    {
        $course = Course::factory()->published()->create(['instructor_id' => $this->instructor->id]);

        $enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'price_paid' => 49.99,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        $payment = Payment::create([
            'enrollment_id' => $enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'stripe_payment_intent_id' => 'pi_test_confirm',
            'amount' => 49.99,
            'currency' => 'usd',
            'status' => PaymentStatus::Pending,
        ]);

        $stripeService = app(StripePaymentService::class);
        $stripeService->confirmPayment('pi_test_confirm');

        $payment->refresh();
        $this->assertEquals(PaymentStatus::Succeeded, $payment->status);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_refund_changes_payment_and_enrollment_status(): void
    {
        $course = Course::factory()->published()->create(['instructor_id' => $this->instructor->id]);

        $enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'price_paid' => 49.99,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        $payment = Payment::create([
            'enrollment_id' => $enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'stripe_payment_intent_id' => null,
            'amount' => 49.99,
            'currency' => 'usd',
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
        ]);

        $stripeService = app(StripePaymentService::class);
        $stripeService->refund($payment);

        $payment->refresh();
        $enrollment->refresh();

        $this->assertEquals(PaymentStatus::Refunded, $payment->status);
        $this->assertNotNull($payment->refunded_at);
        $this->assertEquals(EnrollmentStatus::Refunded, $enrollment->status);
    }

    public function test_cannot_refund_pending_payment(): void
    {
        $course = Course::factory()->published()->create(['instructor_id' => $this->instructor->id]);

        $enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'price_paid' => 49.99,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        $payment = Payment::create([
            'enrollment_id' => $enrollment->id,
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'stripe_payment_intent_id' => 'pi_test_pending',
            'amount' => 49.99,
            'currency' => 'usd',
            'status' => PaymentStatus::Pending,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Can only refund succeeded payments.');

        $stripeService = app(StripePaymentService::class);
        $stripeService->refund($payment);
    }
}
