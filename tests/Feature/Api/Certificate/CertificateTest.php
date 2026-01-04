<?php

namespace Tests\Feature\Api\Certificate;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use App\Services\CertificateService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $instructor;

    private Course $course;

    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        $this->instructor = User::factory()->create();
        $this->instructor->assignRole('instructor');

        $this->course = Course::factory()->published()->create([
            'instructor_id' => $this->instructor->id,
        ]);

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Completed,
            'enrolled_at' => now(),
            'completed_at' => now(),
        ]);
    }

    public function test_certificate_is_generated_on_course_completion(): void
    {
        Sanctum::actingAs($this->student);

        $module = Module::factory()->create(['course_id' => $this->course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => Course::factory()->published()->create([
                'instructor_id' => $this->instructor->id,
            ])->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        $course = $enrollment->course;
        $module2 = Module::factory()->create(['course_id' => $course->id]);
        $lesson2 = Lesson::factory()->create(['module_id' => $module2->id]);

        $this->postJson('/api/lessons/'.$lesson2->id.'/complete');

        $this->assertDatabaseHas('certificates', [
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
        ]);
    }

    public function test_student_can_list_certificates(): void
    {
        Sanctum::actingAs($this->student);

        $service = app(CertificateService::class);
        $service->generate($this->student, $this->course, $this->enrollment);

        $response = $this->getJson('/api/certificates');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'certificate_number', 'course_id', 'issued_at', 'download_url']],
            ]);
    }

    public function test_student_can_view_own_certificate(): void
    {
        Sanctum::actingAs($this->student);

        $service = app(CertificateService::class);
        $certificate = $service->generate($this->student, $this->course, $this->enrollment);

        $response = $this->getJson('/api/certificates/'.$certificate->certificate_number);

        $response->assertOk()
            ->assertJsonPath('data.certificate_number', $certificate->certificate_number)
            ->assertJsonPath('data.course_id', $this->course->id);
    }

    public function test_student_cannot_view_others_certificate(): void
    {
        Sanctum::actingAs($this->student);

        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');

        $otherEnrollment = Enrollment::create([
            'student_id' => $otherStudent->id,
            'course_id' => $this->course->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Completed,
            'enrolled_at' => now(),
            'completed_at' => now(),
        ]);

        $service = app(CertificateService::class);
        $certificate = $service->generate($otherStudent, $this->course, $otherEnrollment);

        $response = $this->getJson('/api/certificates/'.$certificate->certificate_number);

        $response->assertForbidden();
    }

    public function test_student_can_download_certificate_pdf(): void
    {
        Sanctum::actingAs($this->student);

        $service = app(CertificateService::class);
        $certificate = $service->generate($this->student, $this->course, $this->enrollment);

        $response = $this->get('/api/certificates/'.$certificate->certificate_number.'/download');

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_certificate_number_is_unique(): void
    {
        $service = app(CertificateService::class);
        $cert1 = $service->generate($this->student, $this->course, $this->enrollment);

        $otherStudent = User::factory()->create();
        $otherCourse = Course::factory()->published()->create([
            'instructor_id' => $this->instructor->id,
        ]);
        $otherEnrollment = Enrollment::create([
            'student_id' => $otherStudent->id,
            'course_id' => $otherCourse->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Completed,
            'enrolled_at' => now(),
            'completed_at' => now(),
        ]);

        $cert2 = $service->generate($otherStudent, $otherCourse, $otherEnrollment);

        $this->assertNotEquals($cert1->certificate_number, $cert2->certificate_number);
    }

    public function test_duplicate_certificate_generation_returns_existing(): void
    {
        $service = app(CertificateService::class);

        $cert1 = $service->generate($this->student, $this->course, $this->enrollment);
        $cert2 = $service->generate($this->student, $this->course, $this->enrollment);

        $this->assertEquals($cert1->id, $cert2->id);
        $this->assertDatabaseCount('certificates', 1);
    }

    public function test_public_certificate_verification(): void
    {
        $service = app(CertificateService::class);
        $certificate = $service->generate($this->student, $this->course, $this->enrollment);

        $response = $this->getJson('/api/certificates/'.$certificate->certificate_number.'/verify');

        $response->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('certificate_number', $certificate->certificate_number)
            ->assertJsonPath('student_name', $this->student->name)
            ->assertJsonPath('course_title', $this->course->title);
    }

    public function test_invalid_certificate_verification(): void
    {
        $response = $this->getJson('/api/certificates/CERT-INVALID-2026/verify');

        $response->assertNotFound()
            ->assertJsonPath('valid', false);
    }

    public function test_certificate_list_only_shows_own(): void
    {
        Sanctum::actingAs($this->student);

        $service = app(CertificateService::class);
        $service->generate($this->student, $this->course, $this->enrollment);

        $otherStudent = User::factory()->create();
        $otherEnrollment = Enrollment::create([
            'student_id' => $otherStudent->id,
            'course_id' => $this->course->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Completed,
            'enrolled_at' => now(),
            'completed_at' => now(),
        ]);
        $service->generate($otherStudent, $this->course, $otherEnrollment);

        $response = $this->getJson('/api/certificates');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_unauthenticated_cannot_list_certificates(): void
    {
        $response = $this->getJson('/api/certificates');

        $response->assertUnauthorized();
    }
}
