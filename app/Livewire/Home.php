<?php

namespace App\Livewire;

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Module LMS - Learn Something New')]
class Home extends Component
{
    public function render()
    {
        return view('livewire.home', [
            'featuredCourses' => Course::published()
                ->with(['instructor', 'category', 'media'])
                ->withCount('enrollments')
                ->latest('published_at')
                ->take(6)
                ->get(),
            'categories' => Category::active()
                ->withCount(['courses' => fn ($q) => $q->where('status', CourseStatus::Published)])
                ->orderBy('sort_order')
                ->get(),
            'totalCourses' => Course::published()->count(),
            'totalStudents' => \App\Models\User::role('student')->count(),
        ]);
    }
}
