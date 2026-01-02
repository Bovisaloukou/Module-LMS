<?php

namespace App\Livewire;

use App\Enums\CourseLevel;
use App\Models\Category;
use App\Models\Course;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Browse Courses')]
class CoursesCatalog extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $level = '';

    #[Url]
    public string $sort = 'latest';

    public bool $freeOnly = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedLevel(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'category', 'level', 'freeOnly', 'sort']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Course::published()
            ->with(['instructor', 'category', 'media'])
            ->withCount('enrollments')
            ->withAvg('reviews', 'rating');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('short_description', 'like', "%{$this->search}%");
            });
        }

        if ($this->category) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $this->category));
        }

        if ($this->level) {
            $query->where('level', $this->level);
        }

        if ($this->freeOnly) {
            $query->where('is_free', true);
        }

        $query = match ($this->sort) {
            'popular' => $query->orderByDesc('enrollments_count'),
            'price_low' => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            'rating' => $query->orderByDesc('reviews_avg_rating'),
            default => $query->latest('published_at'),
        };

        return view('livewire.courses-catalog', [
            'courses' => $query->paginate(12),
            'categories' => Category::active()->orderBy('sort_order')->get(),
            'levels' => CourseLevel::cases(),
        ]);
    }
}
