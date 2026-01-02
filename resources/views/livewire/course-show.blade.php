<div>
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="border-b border-green-200 bg-green-50 p-4 text-center text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="border-b border-red-200 bg-red-50 p-4 text-center text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Course Header --}}
    <section class="bg-gradient-to-br from-gray-800 to-gray-900 py-12 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <div class="mb-3 flex items-center gap-2">
                        @if($course->category)
                            <span class="rounded-full bg-indigo-500/20 px-3 py-1 text-xs font-medium text-indigo-300">{{ $course->category->name }}</span>
                        @endif
                        <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-gray-300">{{ $course->level->label() }}</span>
                    </div>

                    <h1 class="text-3xl font-bold">{{ $course->title }}</h1>
                    @if($course->subtitle)
                        <p class="mt-2 text-lg text-gray-300">{{ $course->subtitle }}</p>
                    @endif

                    <p class="mt-4 text-sm text-gray-400">
                        By <span class="text-white">{{ $course->instructor->name }}</span>
                        &middot; {{ $course->enrollments_count }} students
                        @if($course->reviews_avg_rating)
                            &middot; {{ number_format($course->reviews_avg_rating, 1) }}/5 ({{ $course->reviews_count }} reviews)
                        @endif
                    </p>
                </div>

                {{-- Enrollment Card --}}
                <div class="rounded-xl bg-white p-6 text-gray-900 shadow-lg">
                    @if($course->getFirstMediaUrl('thumbnail'))
                        <img src="{{ $course->getFirstMediaUrl('thumbnail') }}" alt="{{ $course->title }}" class="mb-4 aspect-video w-full rounded-lg object-cover">
                    @endif

                    <div class="mb-4 text-center">
                        @if($course->is_free)
                            <span class="text-3xl font-bold text-green-600">Free</span>
                        @else
                            <span class="text-3xl font-bold">${{ number_format($course->price, 2) }}</span>
                        @endif
                    </div>

                    @if($enrollment)
                        <a href="{{ route('student.learn', $course->slug) }}" class="block w-full rounded-lg bg-indigo-600 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-700" wire:navigate>
                            Continue Learning
                        </a>
                    @else
                        <button wire:click="enroll" class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                            <span wire:loading.remove wire:target="enroll">{{ $course->is_free ? 'Enroll for Free' : 'Enroll Now' }}</span>
                            <span wire:loading wire:target="enroll">Processing...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Course Content --}}
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-3">
            <div class="lg:col-span-2">
                {{-- What You'll Learn --}}
                @if($course->what_you_learn && count($course->what_you_learn))
                <div class="mb-10">
                    <h2 class="mb-4 text-xl font-bold text-gray-900">What You'll Learn</h2>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach($course->what_you_learn as $item)
                            <div class="flex items-start gap-2">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <span class="text-sm text-gray-700">{{ $item }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Description --}}
                @if($course->description)
                <div class="mb-10">
                    <h2 class="mb-4 text-xl font-bold text-gray-900">Description</h2>
                    <div class="prose prose-sm max-w-none text-gray-700">{!! $course->description !!}</div>
                </div>
                @endif

                {{-- Curriculum --}}
                @if($course->modules->count())
                <div class="mb-10">
                    <h2 class="mb-4 text-xl font-bold text-gray-900">Curriculum</h2>
                    <div class="space-y-3">
                        @foreach($course->modules as $module)
                        <details class="rounded-lg border border-gray-200 bg-white">
                            <summary class="cursor-pointer px-4 py-3 font-medium text-gray-900 hover:bg-gray-50">
                                {{ $module->title }}
                                <span class="ml-2 text-sm text-gray-500">({{ $module->lessons->count() }} lessons)</span>
                            </summary>
                            <ul class="border-t border-gray-100 divide-y divide-gray-100">
                                @foreach($module->lessons as $lesson)
                                <li class="flex items-center justify-between px-4 py-2.5 text-sm text-gray-600">
                                    <span>{{ $lesson->title }}</span>
                                    @if($lesson->duration_minutes)
                                        <span class="text-gray-400">{{ $lesson->duration_minutes }} min</span>
                                    @endif
                                </li>
                                @endforeach
                            </ul>
                        </details>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Reviews --}}
                @if($course->reviews->count())
                <div>
                    <h2 class="mb-4 text-xl font-bold text-gray-900">Student Reviews</h2>
                    <div class="space-y-4">
                        @foreach($course->reviews->take(5) as $review)
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-900">{{ $review->student->name }}</span>
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="h-4 w-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    @endfor
                                </div>
                            </div>
                            @if($review->comment)
                                <p class="mt-2 text-sm text-gray-600">{{ $review->comment }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div>
                {{-- Requirements --}}
                @if($course->requirements && count($course->requirements))
                <div class="mb-8 rounded-lg border border-gray-200 bg-white p-6">
                    <h3 class="mb-3 font-bold text-gray-900">Requirements</h3>
                    <ul class="space-y-2">
                        @foreach($course->requirements as $req)
                            <li class="flex items-start gap-2 text-sm text-gray-600">
                                <span class="text-gray-400">&bull;</span>
                                {{ $req }}
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Course Info --}}
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h3 class="mb-3 font-bold text-gray-900">Course Info</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Level</dt>
                            <dd class="font-medium text-gray-900">{{ $course->level->label() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Language</dt>
                            <dd class="font-medium text-gray-900">{{ strtoupper($course->language ?? 'en') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Modules</dt>
                            <dd class="font-medium text-gray-900">{{ $course->modules->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Lessons</dt>
                            <dd class="font-medium text-gray-900">{{ $course->modules->sum(fn ($m) => $m->lessons->count()) }}</dd>
                        </div>
                        @if($course->duration_minutes)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Duration</dt>
                            <dd class="font-medium text-gray-900">{{ floor($course->duration_minutes / 60) }}h {{ $course->duration_minutes % 60 }}m</dd>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Students</dt>
                            <dd class="font-medium text-gray-900">{{ $course->enrollments_count }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
