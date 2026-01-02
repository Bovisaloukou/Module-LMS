<div>
    {{-- Hero Section --}}
    <section class="bg-gradient-to-br from-indigo-600 to-purple-700 py-20 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">Learn Something New Today</h1>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-indigo-100">Discover {{ $totalCourses }} courses taught by expert instructors. Join {{ $totalStudents }} students already learning.</p>
                <a href="{{ route('courses.catalog') }}" class="mt-8 inline-block rounded-lg bg-white px-8 py-3 text-sm font-semibold text-indigo-600 shadow-sm hover:bg-indigo-50" wire:navigate>Browse Courses</a>
            </div>
        </div>
    </section>

    {{-- Categories --}}
    @if($categories->count())
    <section class="py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="mb-8 text-2xl font-bold text-gray-900">Browse by Category</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach($categories as $category)
                <a href="{{ route('courses.catalog', ['category' => $category->slug]) }}"
                    class="rounded-lg border border-gray-200 bg-white p-4 text-center shadow-sm transition hover:border-indigo-300 hover:shadow-md" wire:navigate>
                    @if($category->icon)
                        <span class="text-2xl">{{ $category->icon }}</span>
                    @endif
                    <h3 class="mt-2 font-semibold text-gray-900">{{ $category->name }}</h3>
                    <p class="text-sm text-gray-500">{{ $category->courses_count }} courses</p>
                </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Featured Courses --}}
    @if($featuredCourses->count())
    <section class="bg-gray-100 py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-900">Latest Courses</h2>
                <a href="{{ route('courses.catalog') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500" wire:navigate>View all &rarr;</a>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($featuredCourses as $course)
                    @include('livewire.partials.course-card', ['course' => $course])
                @endforeach
            </div>
        </div>
    </section>
    @endif
</div>
