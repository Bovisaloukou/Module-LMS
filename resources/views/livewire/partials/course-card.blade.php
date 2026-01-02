<a href="{{ route('courses.show', $course->slug) }}" class="group overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 transition hover:shadow-md" wire:navigate>
    {{-- Thumbnail --}}
    <div class="aspect-video bg-gray-200">
        @if($course->getFirstMediaUrl('thumbnail'))
            <img src="{{ $course->getFirstMediaUrl('thumbnail') }}" alt="{{ $course->title }}" class="h-full w-full object-cover">
        @else
            <div class="flex h-full items-center justify-center bg-gradient-to-br from-indigo-100 to-purple-100">
                <span class="text-4xl text-indigo-300">&#x1F4DA;</span>
            </div>
        @endif
    </div>

    <div class="p-4">
        {{-- Category & Level --}}
        <div class="mb-2 flex items-center gap-2">
            @if($course->category)
                <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ $course->category->name }}</span>
            @endif
            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $course->level->label() }}</span>
        </div>

        {{-- Title --}}
        <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600">{{ $course->title }}</h3>

        {{-- Instructor --}}
        <p class="mt-1 text-sm text-gray-500">{{ $course->instructor->name }}</p>

        {{-- Stats --}}
        <div class="mt-3 flex items-center justify-between">
            <span class="text-sm text-gray-500">{{ $course->enrollments_count }} students</span>
            @if($course->is_free)
                <span class="font-semibold text-green-600">Free</span>
            @else
                <span class="font-semibold text-gray-900">${{ number_format($course->price, 2) }}</span>
            @endif
        </div>
    </div>
</a>
