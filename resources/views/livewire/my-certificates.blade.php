<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900">My Certificates</h1>

    @if($certificates->count())
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($certificates as $certificate)
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <div class="mb-4 flex h-24 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-50 to-purple-50">
                <svg class="h-12 w-12 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            </div>

            <h3 class="font-semibold text-gray-900">{{ $certificate->course->title }}</h3>
            <p class="mt-1 text-sm text-gray-500">Issued {{ $certificate->issued_at->format('M d, Y') }}</p>
            <p class="mt-1 text-xs font-mono text-gray-400">{{ $certificate->certificate_number }}</p>

            <a href="{{ route('certificates.download', $certificate->certificate_number) }}"
                class="mt-4 block w-full rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-center text-sm font-medium text-indigo-700 hover:bg-indigo-100">
                Download PDF
            </a>
        </div>
        @endforeach
    </div>
    @else
    <div class="mt-6 rounded-xl bg-white p-12 text-center shadow-sm ring-1 ring-gray-200">
        <p class="text-gray-500">No certificates yet. Complete a course to earn one!</p>
        <a href="{{ route('student.dashboard') }}" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500" wire:navigate>Go to Dashboard</a>
    </div>
    @endif
</div>
