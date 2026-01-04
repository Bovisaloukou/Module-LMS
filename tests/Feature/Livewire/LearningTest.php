<?php

namespace Tests\Feature\Livewire;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Livewire\Learning\CourseLearn;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LearningTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Course $course;

    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        $this->course = Course::factory()->create(['status' => CourseStatus::Published]);

        $this->enrollment = Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::Active,
        ]);
    }

    public function test_learning_page_requires_auth(): void
    {
        $this->get(route('student.learn', $this->course->slug))
            ->assertRedirect(route('login'));
    }

    public function test_learning_page_requires_enrollment(): void
    {
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');

        $this->actingAs($otherStudent)
            ->get(route('student.learn', $this->course->slug))
            ->assertNotFound();
    }

    public function test_learning_page_renders_with_curriculum(): void
    {
        $module = Module::factory()->create(['course_id' => $this->course->id, 'title' => 'Module 1']);
        Lesson::factory()->create(['module_id' => $module->id, 'title' => 'Lesson 1']);

        Livewire::actingAs($this->student)
            ->test(CourseLearn::class, ['slug' => $this->course->slug])
            ->assertSee('Module 1')
            ->assertSee('Lesson 1');
    }

    public function test_student_can_select_lesson(): void
    {
        $module = Module::factory()->create(['course_id' => $this->course->id]);
        $lesson1 = Lesson::factory()->create(['module_id' => $module->id, 'title' => 'First Lesson', 'content' => 'First content']);
        $lesson2 = Lesson::factory()->create(['module_id' => $module->id, 'title' => 'Second Lesson', 'content' => 'Second content']);

        Livewire::actingAs($this->student)
            ->test(CourseLearn::class, ['slug' => $this->course->slug])
            ->call('selectLesson', $lesson2->id)
            ->assertSee('Second Lesson');
    }

    public function test_student_can_complete_lesson(): void
    {
        $module = Module::factory()->create(['course_id' => $this->course->id]);
        Lesson::factory()->create(['module_id' => $module->id, 'title' => 'Only Lesson']);

        Livewire::actingAs($this->student)
            ->test(CourseLearn::class, ['slug' => $this->course->slug])
            ->call('completeLesson');

        $this->assertDatabaseHas('lesson_progress', [
            'student_id' => $this->student->id,
            'enrollment_id' => $this->enrollment->id,
            'status' => 'completed',
        ]);
    }

    public function test_progress_bar_updates_after_completion(): void
    {
        $module = Module::factory()->create(['course_id' => $this->course->id]);
        $lesson1 = Lesson::factory()->create(['module_id' => $module->id]);
        $lesson2 = Lesson::factory()->create(['module_id' => $module->id]);

        $component = Livewire::actingAs($this->student)
            ->test(CourseLearn::class, ['slug' => $this->course->slug]);

        $component->call('selectLesson', $lesson1->id)
            ->call('completeLesson')
            ->assertSee('1/2');
    }
}
