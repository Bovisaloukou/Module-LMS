<?php

namespace Tests\Feature\Api\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\StripePaymentService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class EnrollmentTest extends TestCase
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

    public function test_student_can_enroll_in_free_course(): void
    {
        Sanctum::actingAs($this->student);

        $course = Course::factory()->published()->free()->create([
            'instructor_id' => $this->instructor->id,
        ]);

        $response = $this->postJson('/api/courses/'.$course->id.'/enroll');

        $response->assertStatus(201)
            ->assertJsonPath('requires_payment', false)
            ->assertJsonPath('enrollment.status', 'active');

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);
    }

    public function test_student_can_enroll_in_paid_course(): void
    {
        Sanctum::actingAs($this->student);

        $course = Course::factory()->published()->create([
            'instructor_id' => $this->instructor->id,
            'price' => 49.99,
            'is_free' => false,
        ]);

        $mock = Mockery::mock(StripePaymentService::class);
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn([
                'id' => 'pi_test_123',
                'client_secret' => 'pi_test_123_secret_456',
            ]);
        $this->app->instance(StripePaymentService::class, $mock);

        $response = $this->postJson('/api/courses/'.$course->id.'/enroll');

        $response->assertStatus(201)
            ->assertJsonPath('requires_payment', true)
            ->assertJsonStructure(['client_secret', 'payment_intent_id']);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_student_cannot_enroll_twice(): void
    {
        Sanctum::actingAs($this->student);

        $course = Course::factory()->published()->free()->create([
            'instructor_id' => $this->instructor->id,
        ]);

        Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        $response = $this->postJson('/api/courses/'.$course->id.'/enroll');

        $response->assertStatus(409)
            ->assertJson(['message' => 'Already enrolled in this course.']);
    }

    public function test_unauthenticated_cannot_enroll(): void
    {
        $course = Course::factory()->published()->free()->create([
            'instructor_id' => $this->instructor->id,
        ]);

        $response = $this->postJson('/api/courses/'.$course->id.'/enroll');

        $response->assertUnauthorized();
    }

    public function test_student_can_list_enrollments(): void
    {
        Sanctum::actingAs($this->student);

        $courses = Course::factory()->published()->count(3)->create([
            'instructor_id' => $this->instructor->id,
        ]);

        foreach ($courses as $course) {
            Enrollment::create([
                'student_id' => $this->student->id,
                'course_id' => $course->id,
                'price_paid' => 0,
                'status' => EnrollmentStatus::Active,
                'enrolled_at' => now(),
            ]);
        }

        $response = $this->getJson('/api/enrollments');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_student_can_view_own_enrollment(): void
    {
        Sanctum::actingAs($this->student);

        $course = Course::factory()->published()->create([
            'instructor_id' => $this->instructor->id,
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        $response = $this->getJson('/api/enrollments/'.$enrollment->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $enrollment->id)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_student_cannot_view_others_enrollment(): void
    {
        Sanctum::actingAs($this->student);

        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');

        $course = Course::factory()->published()->create([
            'instructor_id' => $this->instructor->id,
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $otherStudent->id,
            'course_id' => $course->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        $response = $this->getJson('/api/enrollments/'.$enrollment->id);

        $response->assertForbidden();
    }

    public function test_enrollment_list_only_shows_own_enrollments(): void
    {
        Sanctum::actingAs($this->student);

        $course1 = Course::factory()->published()->create(['instructor_id' => $this->instructor->id]);
        $course2 = Course::factory()->published()->create(['instructor_id' => $this->instructor->id]);

        Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $course1->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        $otherStudent = User::factory()->create();
        Enrollment::create([
            'student_id' => $otherStudent->id,
            'course_id' => $course2->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        $response = $this->getJson('/api/enrollments');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_refunded_enrollment_allows_re_enrollment(): void
    {
        Sanctum::actingAs($this->student);

        $course = Course::factory()->published()->free()->create([
            'instructor_id' => $this->instructor->id,
        ]);

        Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Refunded,
            'enrolled_at' => now(),
        ]);

        $response = $this->postJson('/api/courses/'.$course->id.'/enroll');

        $response->assertStatus(201)
            ->assertJsonPath('requires_payment', false);
    }
}
