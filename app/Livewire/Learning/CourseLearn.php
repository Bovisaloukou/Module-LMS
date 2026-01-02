<?php

namespace App\Livewire\Learning;

use App\Enums\EnrollmentStatus;
use App\Enums\ProgressStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\ProgressService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CourseLearn extends Component
{
    public Course $course;

    public Enrollment $enrollment;

    public ?Lesson $currentLesson = null;

    public function mount(string $slug): void
    {
        $this->course = Course::where('slug', $slug)
            ->with(['modules.lessons'])
            ->firstOrFail();

        $this->enrollment = Enrollment::where('student_id', auth()->id())
            ->where('course_id', $this->course->id)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->firstOrFail();

        // Load the first lesson by default
        $firstLesson = $this->course->modules->flatMap->lessons->first();
        if ($firstLesson) {
            $this->selectLesson($firstLesson->id);
        }
    }

    public function selectLesson(int $lessonId): void
    {
        $this->currentLesson = Lesson::with(['module', 'media'])
            ->findOrFail($lessonId);
    }

    public function completeLesson(ProgressService $progressService): void
    {
        if (! $this->currentLesson) {
            return;
        }

        $progressService->markLessonComplete(
            auth()->user(),
            $this->currentLesson,
            $this->enrollment
        );

        // Auto-advance to next lesson
        $allLessons = $this->course->modules->flatMap->lessons;
        $currentIndex = $allLessons->search(fn ($l) => $l->id === $this->currentLesson->id);
        $nextLesson = $allLessons->get($currentIndex + 1);

        if ($nextLesson) {
            $this->selectLesson($nextLesson->id);
        }
    }

    public function render()
    {
        $lessonProgressMap = LessonProgress::where('student_id', auth()->id())
            ->where('enrollment_id', $this->enrollment->id)
            ->pluck('status', 'lesson_id');

        $allLessons = $this->course->modules->flatMap->lessons;
        $completedCount = $lessonProgressMap->filter(fn ($s) => $s === ProgressStatus::Completed)->count();
        $percentage = $allLessons->count() > 0
            ? round(($completedCount / $allLessons->count()) * 100)
            : 0;

        return view('livewire.learning.course-learn', [
            'lessonProgressMap' => $lessonProgressMap,
            'percentage' => $percentage,
            'completedCount' => $completedCount,
            'totalLessons' => $allLessons->count(),
        ])->title('Learning: '.$this->course->title);
    }
}
