<?php

namespace Tests\Feature\Api\Quiz;

use App\Models\Answer;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstructorQuizTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->instructor = User::factory()->create();
        $this->instructor->assignRole('instructor');

        $this->course = Course::factory()->create([
            'instructor_id' => $this->instructor->id,
        ]);
    }

    public function test_instructor_can_create_quiz_with_questions(): void
    {
        Sanctum::actingAs($this->instructor);

        $response = $this->postJson('/api/instructor/courses/'.$this->course->id.'/quizzes', [
            'title' => 'Module 1 Quiz',
            'description' => 'Test your knowledge',
            'pass_percentage' => 70,
            'max_attempts' => 3,
            'questions' => [
                [
                    'type' => 'single_choice',
                    'question_text' => 'What is Laravel?',
                    'points' => 1,
                    'answers' => [
                        ['answer_text' => 'A PHP framework', 'is_correct' => true],
                        ['answer_text' => 'A database', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'question_text' => 'Laravel uses MVC pattern?',
                    'points' => 1,
                    'answers' => [
                        ['answer_text' => 'True', 'is_correct' => true],
                        ['answer_text' => 'False', 'is_correct' => false],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Module 1 Quiz')
            ->assertJsonPath('data.questions_count', 2);

        $this->assertDatabaseHas('quizzes', [
            'course_id' => $this->course->id,
            'title' => 'Module 1 Quiz',
        ]);

        $this->assertDatabaseCount('questions', 2);
        $this->assertDatabaseCount('answers', 4);
    }

    public function test_instructor_can_list_course_quizzes(): void
    {
        Sanctum::actingAs($this->instructor);

        Quiz::factory()->count(3)->create([
            'course_id' => $this->course->id,
        ]);

        $response = $this->getJson('/api/instructor/courses/'.$this->course->id.'/quizzes');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_instructor_can_view_quiz_with_correct_answers(): void
    {
        Sanctum::actingAs($this->instructor);

        $quiz = Quiz::factory()->create(['course_id' => $this->course->id]);
        $question = Question::factory()->create(['quiz_id' => $quiz->id]);
        Answer::factory()->correct()->create(['question_id' => $question->id]);
        Answer::factory()->create(['question_id' => $question->id]);

        $response = $this->getJson('/api/instructor/courses/'.$this->course->id.'/quizzes/'.$quiz->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $quiz->id)
            ->assertJsonStructure([
                'data' => ['questions' => [['answers']]],
            ]);
    }

    public function test_instructor_can_publish_quiz(): void
    {
        Sanctum::actingAs($this->instructor);

        $quiz = Quiz::factory()->unpublished()->create([
            'course_id' => $this->course->id,
        ]);

        $response = $this->postJson('/api/instructor/courses/'.$this->course->id.'/quizzes/'.$quiz->id.'/publish');

        $response->assertOk()
            ->assertJsonPath('data.is_published', true);
    }

    public function test_instructor_can_delete_quiz(): void
    {
        Sanctum::actingAs($this->instructor);

        $quiz = Quiz::factory()->create([
            'course_id' => $this->course->id,
        ]);

        $response = $this->deleteJson('/api/instructor/courses/'.$this->course->id.'/quizzes/'.$quiz->id);

        $response->assertOk();

        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
    }

    public function test_instructor_cannot_manage_other_instructors_quizzes(): void
    {
        $otherInstructor = User::factory()->create();
        $otherInstructor->assignRole('instructor');
        Sanctum::actingAs($otherInstructor);

        $response = $this->getJson('/api/instructor/courses/'.$this->course->id.'/quizzes');

        $response->assertForbidden();
    }

    public function test_student_cannot_access_instructor_quiz_routes(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        Sanctum::actingAs($student);

        $response = $this->getJson('/api/instructor/courses/'.$this->course->id.'/quizzes');

        $response->assertForbidden();
    }

    public function test_create_quiz_requires_questions(): void
    {
        Sanctum::actingAs($this->instructor);

        $response = $this->postJson('/api/instructor/courses/'.$this->course->id.'/quizzes', [
            'title' => 'Empty Quiz',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['questions']);
    }
}
