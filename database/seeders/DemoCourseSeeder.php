<?php

namespace Database\Seeders;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonType;
use App\Enums\ProgressStatus;
use App\Enums\QuestionType;
use App\Models\Answer;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCourseSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::where('email', 'instructor@lms.test')->first();
        $student = User::where('email', 'student@lms.test')->first();
        $webDev = Category::where('slug', 'web-development')->first();
        $dataSci = Category::where('slug', 'data-science')->first();

        // Course 1: Laravel Masterclass
        $course1 = Course::create([
            'instructor_id' => $instructor->id,
            'category_id' => $webDev->id,
            'title' => 'Laravel Masterclass: Build Modern Web Apps',
            'slug' => 'laravel-masterclass',
            'subtitle' => 'From zero to production with Laravel 11',
            'description' => 'A comprehensive course covering everything you need to know about Laravel. From routing and controllers to Eloquent, queues, and deployment.',
            'short_description' => 'Master Laravel from the ground up with real-world projects.',
            'price' => 49.99,
            'is_free' => false,
            'level' => CourseLevel::Beginner,
            'language' => 'en',
            'duration_minutes' => 480,
            'requirements' => ['Basic PHP knowledge', 'HTML & CSS fundamentals', 'A code editor'],
            'what_you_learn' => ['Build full-stack web applications', 'Master Eloquent ORM', 'Deploy to production', 'Write tests'],
            'status' => CourseStatus::Published,
            'published_at' => now()->subDays(30),
        ]);

        $this->createLaravelModules($course1);
        $this->createLaravelQuiz($course1);

        // Course 2: Free Intro to Python
        $course2 = Course::create([
            'instructor_id' => $instructor->id,
            'category_id' => $dataSci->id,
            'title' => 'Introduction to Python Programming',
            'slug' => 'intro-to-python',
            'subtitle' => 'Your first steps into programming',
            'description' => 'Learn Python from scratch. This free course covers variables, loops, functions, and object-oriented programming.',
            'short_description' => 'Free beginner-friendly Python course.',
            'price' => 0,
            'is_free' => true,
            'level' => CourseLevel::Beginner,
            'language' => 'en',
            'duration_minutes' => 180,
            'requirements' => ['No prior programming experience needed'],
            'what_you_learn' => ['Python fundamentals', 'Control flow & loops', 'Functions & modules', 'OOP basics'],
            'status' => CourseStatus::Published,
            'published_at' => now()->subDays(15),
        ]);

        $this->createPythonModules($course2);

        // Course 3: Advanced API Design (draft)
        Course::create([
            'instructor_id' => $instructor->id,
            'category_id' => $webDev->id,
            'title' => 'Advanced API Design with Laravel',
            'slug' => 'advanced-api-design',
            'subtitle' => 'RESTful APIs, GraphQL, and beyond',
            'description' => 'Deep dive into API architecture, authentication, rate limiting, versioning, and documentation.',
            'short_description' => 'Learn to build production-grade APIs.',
            'price' => 79.99,
            'is_free' => false,
            'level' => CourseLevel::Advanced,
            'language' => 'en',
            'duration_minutes' => 360,
            'requirements' => ['Intermediate Laravel knowledge', 'Understanding of HTTP'],
            'what_you_learn' => ['RESTful API design patterns', 'API authentication & authorization', 'Rate limiting & caching', 'API documentation'],
            'status' => CourseStatus::Draft,
        ]);

        // Enrollments & progress for demo student
        $this->createDemoEnrollments($student, $course1, $course2);

        // Reviews
        $this->createDemoReviews($course1, $course2);

        // Discussions
        $this->createDemoDiscussions($course1, $student, $instructor);
    }

    private function createLaravelModules(Course $course): void
    {
        $module1 = Module::create([
            'course_id' => $course->id,
            'title' => 'Getting Started',
            'slug' => 'getting-started',
            'description' => 'Setting up your development environment and creating your first Laravel project.',
            'sort_order' => 0,
            'is_published' => true,
        ]);

        Lesson::create(['module_id' => $module1->id, 'title' => 'Introduction to Laravel', 'slug' => 'intro-to-laravel', 'type' => LessonType::Video, 'content' => '<p>Welcome to the Laravel Masterclass! In this course, you will learn to build modern web applications using the most popular PHP framework.</p><p>Laravel provides an expressive, elegant syntax that makes development enjoyable. Let\'s get started!</p>', 'duration_minutes' => 10, 'sort_order' => 0, 'is_free_preview' => true, 'is_published' => true]);
        Lesson::create(['module_id' => $module1->id, 'title' => 'Installation & Setup', 'slug' => 'installation-setup', 'type' => LessonType::Video, 'content' => '<p>To install Laravel, you need PHP 8.2+ and Composer. Run:</p><pre><code>composer create-project laravel/laravel my-app</code></pre><p>Then start the dev server with <code>php artisan serve</code>.</p>', 'duration_minutes' => 15, 'sort_order' => 1, 'is_published' => true]);
        Lesson::create(['module_id' => $module1->id, 'title' => 'Project Structure', 'slug' => 'project-structure', 'type' => LessonType::Text, 'content' => '<h3>Key Directories</h3><ul><li><strong>app/</strong> - Application logic (Models, Controllers, Services)</li><li><strong>routes/</strong> - Route definitions (web.php, api.php)</li><li><strong>resources/</strong> - Views, CSS, JavaScript</li><li><strong>database/</strong> - Migrations, seeders, factories</li><li><strong>config/</strong> - Configuration files</li></ul>', 'duration_minutes' => 8, 'sort_order' => 2, 'is_published' => true]);

        $module2 = Module::create([
            'course_id' => $course->id,
            'title' => 'Routing & Controllers',
            'slug' => 'routing-controllers',
            'description' => 'Learn how to handle HTTP requests in Laravel.',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        Lesson::create(['module_id' => $module2->id, 'title' => 'Basic Routing', 'slug' => 'basic-routing', 'type' => LessonType::Video, 'content' => '<p>Laravel routes are defined in <code>routes/web.php</code> for web routes and <code>routes/api.php</code> for API routes.</p><pre><code>Route::get(\'/hello\', function () {\n    return \'Hello World!\';\n});</code></pre>', 'duration_minutes' => 20, 'sort_order' => 0, 'is_published' => true]);
        Lesson::create(['module_id' => $module2->id, 'title' => 'Controllers', 'slug' => 'controllers', 'type' => LessonType::Video, 'content' => '<p>Controllers group related request handling logic. Create one with:</p><pre><code>php artisan make:controller UserController</code></pre>', 'duration_minutes' => 25, 'sort_order' => 1, 'is_published' => true]);

        $module3 = Module::create([
            'course_id' => $course->id,
            'title' => 'Eloquent ORM',
            'slug' => 'eloquent-orm',
            'description' => 'Master database interactions with Eloquent.',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        Lesson::create(['module_id' => $module3->id, 'title' => 'Models & Migrations', 'slug' => 'models-migrations', 'type' => LessonType::Video, 'content' => '<p>Eloquent models represent database tables. Create a model with a migration:</p><pre><code>php artisan make:model Post -m</code></pre>', 'duration_minutes' => 30, 'sort_order' => 0, 'is_published' => true]);
        Lesson::create(['module_id' => $module3->id, 'title' => 'Relationships', 'slug' => 'relationships', 'type' => LessonType::Video, 'content' => '<p>Eloquent supports hasOne, hasMany, belongsTo, belongsToMany, and polymorphic relationships.</p>', 'duration_minutes' => 35, 'sort_order' => 1, 'is_published' => true]);
        Lesson::create(['module_id' => $module3->id, 'title' => 'Eloquent Cheat Sheet', 'slug' => 'eloquent-cheat-sheet', 'type' => LessonType::Pdf, 'content' => '<p>Download the Eloquent cheat sheet for a quick reference.</p>', 'duration_minutes' => 5, 'sort_order' => 2, 'is_published' => true]);
    }

    private function createPythonModules(Course $course): void
    {
        $module1 = Module::create([
            'course_id' => $course->id,
            'title' => 'Python Basics',
            'slug' => 'python-basics',
            'description' => 'Variables, data types, and basic operations.',
            'sort_order' => 0,
            'is_published' => true,
        ]);

        Lesson::create(['module_id' => $module1->id, 'title' => 'What is Python?', 'slug' => 'what-is-python', 'type' => LessonType::Video, 'content' => '<p>Python is a high-level, interpreted programming language known for its readability and versatility.</p>', 'duration_minutes' => 10, 'sort_order' => 0, 'is_free_preview' => true, 'is_published' => true]);
        Lesson::create(['module_id' => $module1->id, 'title' => 'Variables & Data Types', 'slug' => 'variables-data-types', 'type' => LessonType::Video, 'content' => '<p>Python supports strings, integers, floats, booleans, lists, tuples, and dictionaries.</p><pre><code>name = "Alice"\nage = 25\nis_student = True</code></pre>', 'duration_minutes' => 20, 'sort_order' => 1, 'is_published' => true]);

        $module2 = Module::create([
            'course_id' => $course->id,
            'title' => 'Control Flow',
            'slug' => 'control-flow',
            'description' => 'If statements, loops, and logic.',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        Lesson::create(['module_id' => $module2->id, 'title' => 'If/Else Statements', 'slug' => 'if-else', 'type' => LessonType::Video, 'content' => '<p>Conditional logic in Python uses if, elif, and else keywords.</p>', 'duration_minutes' => 15, 'sort_order' => 0, 'is_published' => true]);
        Lesson::create(['module_id' => $module2->id, 'title' => 'Loops', 'slug' => 'loops', 'type' => LessonType::Video, 'content' => '<p>Python has for loops and while loops for iteration.</p><pre><code>for i in range(5):\n    print(i)</code></pre>', 'duration_minutes' => 20, 'sort_order' => 1, 'is_published' => true]);
    }

    private function createLaravelQuiz(Course $course): void
    {
        $quiz = Quiz::create([
            'course_id' => $course->id,
            'title' => 'Laravel Fundamentals Quiz',
            'description' => 'Test your understanding of Laravel basics.',
            'pass_percentage' => 70,
            'max_attempts' => 3,
            'is_published' => true,
        ]);

        $q1 = Question::create(['quiz_id' => $quiz->id, 'type' => QuestionType::SingleChoice, 'question_text' => 'What command creates a new Laravel project?', 'points' => 1, 'sort_order' => 0]);
        Answer::create(['question_id' => $q1->id, 'answer_text' => 'composer create-project laravel/laravel', 'is_correct' => true, 'sort_order' => 0]);
        Answer::create(['question_id' => $q1->id, 'answer_text' => 'npm init laravel', 'is_correct' => false, 'sort_order' => 1]);
        Answer::create(['question_id' => $q1->id, 'answer_text' => 'pip install laravel', 'is_correct' => false, 'sort_order' => 2]);

        $q2 = Question::create(['quiz_id' => $quiz->id, 'type' => QuestionType::TrueFalse, 'question_text' => 'Eloquent is Laravel\'s ORM for database interactions.', 'points' => 1, 'sort_order' => 1]);
        Answer::create(['question_id' => $q2->id, 'answer_text' => 'True', 'is_correct' => true, 'sort_order' => 0]);
        Answer::create(['question_id' => $q2->id, 'answer_text' => 'False', 'is_correct' => false, 'sort_order' => 1]);

        $q3 = Question::create(['quiz_id' => $quiz->id, 'type' => QuestionType::SingleChoice, 'question_text' => 'Which file defines API routes in Laravel?', 'points' => 1, 'sort_order' => 2]);
        Answer::create(['question_id' => $q3->id, 'answer_text' => 'routes/api.php', 'is_correct' => true, 'sort_order' => 0]);
        Answer::create(['question_id' => $q3->id, 'answer_text' => 'routes/web.php', 'is_correct' => false, 'sort_order' => 1]);
        Answer::create(['question_id' => $q3->id, 'answer_text' => 'routes/console.php', 'is_correct' => false, 'sort_order' => 2]);

        $q4 = Question::create(['quiz_id' => $quiz->id, 'type' => QuestionType::ShortAnswer, 'question_text' => 'What Artisan command starts the development server?', 'points' => 1, 'sort_order' => 3]);
        Answer::create(['question_id' => $q4->id, 'answer_text' => 'php artisan serve', 'is_correct' => true, 'sort_order' => 0]);
    }

    private function createDemoEnrollments(User $student, Course $course1, Course $course2): void
    {
        // Student enrolled in Laravel course with partial progress
        $enrollment1 = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course1->id,
            'price_paid' => 49.99,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now()->subDays(20),
        ]);

        $lessons = $course1->lessons()->orderBy('id')->get();
        $completedCount = min(5, $lessons->count());

        for ($i = 0; $i < $completedCount; $i++) {
            LessonProgress::create([
                'student_id' => $student->id,
                'lesson_id' => $lessons[$i]->id,
                'enrollment_id' => $enrollment1->id,
                'status' => ProgressStatus::Completed,
                'completed_at' => now()->subDays(20 - $i),
                'watch_time_seconds' => $lessons[$i]->duration_minutes * 60,
            ]);
        }

        $totalLessons = $lessons->count();
        $pct = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100, 2) : 0;

        CourseProgress::create([
            'student_id' => $student->id,
            'course_id' => $course1->id,
            'enrollment_id' => $enrollment1->id,
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedCount,
            'percentage' => $pct,
            'status' => ProgressStatus::InProgress,
            'last_lesson_id' => $lessons[$completedCount - 1]->id,
            'last_accessed_at' => now()->subDay(),
        ]);

        // Student enrolled in Python course (free, completed)
        $enrollment2 = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course2->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Completed,
            'enrolled_at' => now()->subDays(10),
            'completed_at' => now()->subDays(3),
        ]);

        $pythonLessons = $course2->lessons()->orderBy('id')->get();
        foreach ($pythonLessons as $lesson) {
            LessonProgress::create([
                'student_id' => $student->id,
                'lesson_id' => $lesson->id,
                'enrollment_id' => $enrollment2->id,
                'status' => ProgressStatus::Completed,
                'completed_at' => now()->subDays(5),
                'watch_time_seconds' => $lesson->duration_minutes * 60,
            ]);
        }

        CourseProgress::create([
            'student_id' => $student->id,
            'course_id' => $course2->id,
            'enrollment_id' => $enrollment2->id,
            'total_lessons' => $pythonLessons->count(),
            'completed_lessons' => $pythonLessons->count(),
            'percentage' => 100,
            'status' => ProgressStatus::Completed,
            'completed_at' => now()->subDays(3),
        ]);

        // Extra students enrolled via factory
        $extraStudents = User::factory()->count(5)->create();
        foreach ($extraStudents as $s) {
            $s->assignRole('student');
            Enrollment::create([
                'student_id' => $s->id,
                'course_id' => $course1->id,
                'price_paid' => 49.99,
                'status' => EnrollmentStatus::Active,
                'enrolled_at' => now()->subDays(rand(1, 25)),
            ]);
        }
    }

    private function createDemoReviews(Course $course1, Course $course2): void
    {
        $reviewData = [
            ['rating' => 5, 'comment' => 'Excellent course! The explanations are clear and the projects are very practical. Highly recommended for beginners.'],
            ['rating' => 4, 'comment' => 'Great content overall. Would love to see more advanced topics covered in a follow-up course.'],
            ['rating' => 5, 'comment' => 'Best Laravel course I\'ve taken. The instructor really knows how to explain complex concepts simply.'],
            ['rating' => 4, 'comment' => 'Very well structured. The Eloquent section was particularly helpful for my work.'],
        ];

        foreach ($reviewData as $data) {
            $reviewStudent = User::factory()->create();
            $reviewStudent->assignRole('student');

            $enrollment = Enrollment::create([
                'student_id' => $reviewStudent->id,
                'course_id' => $course1->id,
                'price_paid' => 49.99,
                'status' => EnrollmentStatus::Active,
                'enrolled_at' => now()->subDays(rand(5, 25)),
            ]);

            Review::create([
                'student_id' => $reviewStudent->id,
                'course_id' => $course1->id,
                'enrollment_id' => $enrollment->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'],
                'is_approved' => true,
            ]);
        }

        // Python course review
        $pyStudent = User::factory()->create();
        $pyStudent->assignRole('student');
        $pyEnrollment = Enrollment::create([
            'student_id' => $pyStudent->id,
            'course_id' => $course2->id,
            'price_paid' => 0,
            'status' => EnrollmentStatus::Active,
            'enrolled_at' => now()->subDays(8),
        ]);
        Review::create([
            'student_id' => $pyStudent->id,
            'course_id' => $course2->id,
            'enrollment_id' => $pyEnrollment->id,
            'rating' => 5,
            'comment' => 'Perfect introduction to Python. Free and high quality!',
            'is_approved' => true,
        ]);
    }

    private function createDemoDiscussions(Course $course, User $student, User $instructor): void
    {
        $lesson = $course->lessons()->first();
        if (! $lesson) {
            return;
        }

        $discussion = Discussion::create([
            'lesson_id' => $lesson->id,
            'user_id' => $student->id,
            'title' => 'How to set up Valet on Linux?',
            'body' => 'The lesson mentions Laravel Valet for macOS. Is there an equivalent for Linux? I\'m running Ubuntu 22.04.',
            'is_resolved' => true,
        ]);

        $reply = DiscussionReply::create([
            'discussion_id' => $discussion->id,
            'user_id' => $instructor->id,
            'body' => 'Great question! On Linux, you can use Laravel Valet Linux (https://github.com/cpriego/valet-linux) or simply use `php artisan serve` for development. Docker with Laravel Sail is also a popular choice.',
            'is_solution' => true,
        ]);

        $discussion2 = Discussion::create([
            'lesson_id' => $lesson->id,
            'user_id' => $student->id,
            'title' => 'Difference between require and require-dev in Composer?',
            'body' => 'When should I use require-dev vs require in composer.json?',
            'is_resolved' => false,
        ]);
    }
}
