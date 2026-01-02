<?php

namespace App\Livewire;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CourseShow extends Component
{
    public Course $course;

    public ?Enrollment $enrollment = null;

    public function mount(string $slug): void
    {
        $this->course = Course::published()
            ->where('slug', $slug)
            ->with([
                'instructor',
                'category',
                'modules.lessons',
                'media',
                'reviews' => fn ($q) => $q->where('is_approved', true)->with('student')->latest(),
            ])
            ->withCount(['enrollments', 'reviews' => fn ($q) => $q->where('is_approved', true)])
            ->withAvg(['reviews' => fn ($q) => $q->where('is_approved', true)], 'rating')
            ->firstOrFail();

        if (auth()->check()) {
            $this->enrollment = Enrollment::where('student_id', auth()->id())
                ->where('course_id', $this->course->id)
                ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
                ->first();
        }
    }

    public function enroll(EnrollmentService $enrollmentService): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if ($this->enrollment) {
            return;
        }

        try {
            $result = $enrollmentService->enroll(auth()->user(), $this->course);

            if ($result['requires_payment']) {
                $this->dispatch('payment-required', clientSecret: $result['client_secret']);

                return;
            }

            $this->enrollment = $result['enrollment'];
            session()->flash('success', 'Successfully enrolled!');
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.course-show')
            ->title($this->course->title);
    }
}
