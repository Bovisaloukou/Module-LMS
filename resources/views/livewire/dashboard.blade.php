<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900">My Dashboard</h1>

    {{-- Stats --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <p class="text-sm font-medium text-gray-500">Active Courses</p>
            <p class="mt-1 text-3xl font-bold text-indigo-600">{{ $activeCount }}</p>
        </div>
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <p class="text-sm font-medium text-gray-500">Completed</p>
            <p class="mt-1 text-3xl font-bold text-green-600">{{ $completedCount }}</p>
        </div>
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <p class="text-sm font-medium text-gray-500">Certificates</p>
            <p class="mt-1 text-3xl font-bold text-purple-600">{{ $certificatesCount }}</p>
        </div>
    </div>

    {{-- Enrolled Courses --}}
    <h2 class="mt-10 text-xl font-bold text-gray-900">My Courses</h2>

    @if($enrollments->count())
    <div class="mt-4 space-y-4">
        @foreach($enrollments as $enrollment)
        <div class="flex items-center gap-6 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
            {{-- Thumbnail --}}
            <div class="hidden h-20 w-32 shrink-0 overflow-hidden rounded-lg bg-gray-100 sm:block">
                @if($enrollment->course->getFirstMediaUrl('thumbnail'))
                    <img src="{{ $enrollment->course->getFirstMediaUrl('thumbnail') }}" class="h-full w-full object-cover" alt="">
                @else
                    <div class="flex h-full items-center justify-center bg-gradient-to-br from-indigo-100 to-purple-100">
                        <span class="text-2xl text-indigo-300">&#x1F4DA;</span>
                    </div>
                @endif
            </div>

            {{-- Info --}}
            <div class="flex-1">
                <h3 class="font-semibold text-gray-900">{{ $enrollment->course->title }}</h3>
                <p class="mt-0.5 text-sm text-gray-500">{{ $enrollment->course->instructor->name }}</p>

                {{-- Progress Bar --}}
                @php
                    $progress = $enrollment->progress;
                    $pct = $progress ? $progress->percentage : 0;
                @endphp
                <div class="mt-2 flex items-center gap-3">
                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200">
                        <div class="h-full rounded-full bg-indigo-600 transition-all" style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="text-xs font-medium text-gray-500">{{ round($pct) }}%</span>
                </div>
            </div>

            {{-- Action --}}
            <div class="shrink-0">
                @if($enrollment->status === \App\Enums\EnrollmentStatus::Completed)
                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">Completed</span>
                @else
                    <a href="{{ route('student.learn', $enrollment->course->slug) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" wire:navigate>Continue</a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="mt-4 rounded-xl bg-white p-12 text-center shadow-sm ring-1 ring-gray-200">
        <p class="text-gray-500">You haven't enrolled in any courses yet.</p>
        <a href="{{ route('courses.catalog') }}" class="mt-4 inline-block rounded-lg bg-indigo-600 px-6 py-2 text-sm font-medium text-white hover:bg-indigo-700" wire:navigate>Browse Courses</a>
    </div>
    @endif
</div>
