<?php

namespace App\Livewire;

use App\Enums\EnrollmentStatus;
use App\Models\Certificate;
use App\Models\Enrollment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('My Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $studentId = auth()->id();

        $enrollments = Enrollment::where('student_id', $studentId)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->with(['course.instructor', 'course.media', 'progress'])
            ->latest('enrolled_at')
            ->get();

        return view('livewire.dashboard', [
            'enrollments' => $enrollments,
            'activeCount' => $enrollments->where('status', EnrollmentStatus::Active)->count(),
            'completedCount' => $enrollments->where('status', EnrollmentStatus::Completed)->count(),
            'certificatesCount' => Certificate::where('student_id', $studentId)->count(),
        ]);
    }
}
