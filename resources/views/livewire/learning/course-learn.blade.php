<div class="flex h-[calc(100vh-4rem)]">
    {{-- Sidebar: Curriculum --}}
    <aside class="hidden w-80 shrink-0 overflow-y-auto border-r border-gray-200 bg-white md:block">
        <div class="p-4">
            <h2 class="text-sm font-bold text-gray-900 truncate">{{ $course->title }}</h2>
            <div class="mt-2 flex items-center gap-2">
                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-200">
                    <div class="h-full rounded-full bg-indigo-600 transition-all" style="width: {{ $percentage }}%"></div>
                </div>
                <span class="text-xs text-gray-500">{{ $completedCount }}/{{ $totalLessons }}</span>
            </div>
        </div>

        <nav class="pb-4">
            @foreach($course->modules as $module)
            <div class="mt-2">
                <h3 class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ $module->title }}</h3>
                <ul>
                    @foreach($module->lessons as $lesson)
                    @php
                        $status = $lessonProgressMap[$lesson->id] ?? null;
                        $isActive = $currentLesson && $currentLesson->id === $lesson->id;
                        $isCompleted = $status?->value === 'completed';
                    @endphp
                    <li>
                        <button wire:click="selectLesson({{ $lesson->id }})"
                            class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm transition {{ $isActive ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
                            @if($isCompleted)
                                <svg class="h-4 w-4 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            @else
                                <span class="h-4 w-4 shrink-0 rounded-full border-2 {{ $isActive ? 'border-indigo-400' : 'border-gray-300' }}"></span>
                            @endif
                            <span class="truncate">{{ $lesson->title }}</span>
                        </button>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </nav>
    </aside>

    {{-- Main Content: Lesson --}}
    <main class="flex-1 overflow-y-auto bg-gray-50">
        @if($currentLesson)
        <div class="mx-auto max-w-4xl px-6 py-8">
            {{-- Lesson Header --}}
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">{{ $currentLesson->module->title }}</p>
                    <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $currentLesson->title }}</h1>
                    <span class="mt-1 inline-block rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $currentLesson->type->label() }}</span>
                </div>

                @php
                    $lessonStatus = $lessonProgressMap[$currentLesson->id] ?? null;
                    $isLessonCompleted = $lessonStatus?->value === 'completed';
                @endphp

                @if(!$isLessonCompleted)
                <button wire:click="completeLesson" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    <span wire:loading.remove wire:target="completeLesson">Mark Complete</span>
                    <span wire:loading wire:target="completeLesson">Saving...</span>
                </button>
                @else
                <span class="flex items-center gap-1 text-sm font-medium text-green-600">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Completed
                </span>
                @endif
            </div>

            {{-- Video --}}
            @if($currentLesson->video_url)
            <div class="mb-6 aspect-video overflow-hidden rounded-xl bg-black">
                <iframe src="{{ $currentLesson->video_url }}" class="h-full w-full" allowfullscreen></iframe>
            </div>
            @endif

            {{-- Content --}}
            @if($currentLesson->content)
            <div class="prose prose-sm max-w-none rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                {!! $currentLesson->content !!}
            </div>
            @endif

            {{-- Quiz link --}}
            @if($currentLesson->quiz && $currentLesson->quiz->is_published)
            <div class="mt-6 rounded-xl border border-indigo-200 bg-indigo-50 p-6 text-center">
                <h3 class="font-semibold text-indigo-900">{{ $currentLesson->quiz->title }}</h3>
                <p class="mt-1 text-sm text-indigo-700">{{ $currentLesson->quiz->description }}</p>
                <a href="{{ route('student.quiz', $currentLesson->quiz->id) }}" class="mt-4 inline-block rounded-lg bg-indigo-600 px-6 py-2 text-sm font-medium text-white hover:bg-indigo-700" wire:navigate>Take Quiz</a>
            </div>
            @endif
        </div>
        @else
        <div class="flex h-full items-center justify-center text-gray-400">
            <p>Select a lesson from the sidebar to begin.</p>
        </div>
        @endif
    </main>
</div>
