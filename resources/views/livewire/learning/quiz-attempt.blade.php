<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
    {{-- Flash Messages --}}
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Quiz Header --}}
    <div class="mb-6">
        <a href="{{ route('student.learn', $quiz->course->slug) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500" wire:navigate>&larr; Back to course</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $quiz->title }}</h1>
        @if($quiz->description)
            <p class="mt-1 text-gray-600">{{ $quiz->description }}</p>
        @endif
        <div class="mt-2 flex items-center gap-4 text-sm text-gray-500">
            <span>{{ $quiz->questions->count() }} questions</span>
            <span>Pass: {{ $quiz->pass_percentage }}%</span>
            @if($quiz->max_attempts > 0)
                <span>Max attempts: {{ $quiz->max_attempts }}</span>
            @endif
        </div>
    </div>

    {{-- Results View --}}
    @if($showResults && $completedAttempt)
    <div class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-200">
        <div class="text-center">
            @if($completedAttempt->passed)
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-8 w-8 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                </div>
                <h2 class="text-2xl font-bold text-green-600">Passed!</h2>
            @else
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-8 w-8 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                </div>
                <h2 class="text-2xl font-bold text-red-600">Not Passed</h2>
            @endif

            <p class="mt-2 text-4xl font-bold text-gray-900">{{ round($completedAttempt->score) }}%</p>
            <p class="mt-1 text-sm text-gray-500">{{ $completedAttempt->earned_points }}/{{ $completedAttempt->total_points }} points</p>
        </div>

        <div class="mt-6 flex justify-center gap-4">
            <a href="{{ route('student.learn', $quiz->course->slug) }}" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300" wire:navigate>Back to Course</a>
            @if(app(App\Services\QuizService::class)->canAttempt(auth()->user(), $quiz))
                <button wire:click="$set('showResults', false)" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Try Again</button>
            @endif
        </div>
    </div>

    {{-- Quiz Form --}}
    @elseif($attempt)
    <form wire:submit="submitQuiz" class="space-y-6">
        @foreach($quiz->questions as $index => $question)
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <p class="mb-4 font-medium text-gray-900">
                <span class="text-indigo-600">Q{{ $index + 1 }}.</span> {{ $question->question_text }}
                <span class="ml-2 text-sm text-gray-400">({{ $question->points }} pts)</span>
            </p>

            @if($question->type->value === 'single_choice' || $question->type->value === 'true_false')
                <div class="space-y-2">
                    @foreach($question->answers as $answer)
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 p-3 transition hover:bg-gray-50">
                        <input type="radio" wire:model="answers.{{ $question->id }}.answer_id" value="{{ $answer->id }}"
                            class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">{{ $answer->answer_text }}</span>
                    </label>
                    @endforeach
                </div>
            @elseif($question->type->value === 'multiple_choice')
                <div class="space-y-2">
                    @foreach($question->answers as $answer)
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 p-3 transition hover:bg-gray-50">
                        <input type="checkbox" wire:model="answers.{{ $question->id }}.answer_ids" value="{{ $answer->id }}"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">{{ $answer->answer_text }}</span>
                    </label>
                    @endforeach
                </div>
            @elseif($question->type->value === 'short_answer')
                <input type="text" wire:model="answers.{{ $question->id }}.text_answer" placeholder="Type your answer..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            @endif
        </div>
        @endforeach

        <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            <span wire:loading.remove wire:target="submitQuiz">Submit Quiz</span>
            <span wire:loading wire:target="submitQuiz">Submitting...</span>
        </button>
    </form>

    {{-- Start Screen --}}
    @else
    <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-200">
        <h2 class="text-xl font-bold text-gray-900">Ready to take the quiz?</h2>
        <p class="mt-2 text-gray-600">You'll have {{ $quiz->questions->count() }} questions. You need {{ $quiz->pass_percentage }}% to pass.</p>

        <button wire:click="startQuiz" class="mt-6 rounded-lg bg-indigo-600 px-8 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
            <span wire:loading.remove wire:target="startQuiz">Start Quiz</span>
            <span wire:loading wire:target="startQuiz">Starting...</span>
        </button>
    </div>

    {{-- Previous Attempts --}}
    @if($previousAttempts->count())
    <div class="mt-8">
        <h3 class="mb-4 text-lg font-bold text-gray-900">Previous Attempts</h3>
        <div class="space-y-2">
            @foreach($previousAttempts as $prev)
            <div class="flex items-center justify-between rounded-lg bg-white p-4 ring-1 ring-gray-200">
                <div>
                    <span class="text-sm text-gray-500">{{ $prev->completed_at->format('M d, Y H:i') }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium {{ $prev->passed ? 'text-green-600' : 'text-red-600' }}">{{ round($prev->score) }}%</span>
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $prev->passed ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $prev->passed ? 'Passed' : 'Failed' }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endif
</div>
