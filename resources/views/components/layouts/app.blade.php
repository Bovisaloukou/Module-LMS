<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Module LMS' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    {{-- Navigation --}}
    <nav class="border-b border-gray-200 bg-white shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center gap-8">
                    <a href="/" class="text-xl font-bold text-indigo-600" wire:navigate>Module LMS</a>
                    <div class="hidden items-center gap-6 md:flex">
                        <a href="{{ route('courses.catalog') }}" class="text-sm font-medium text-gray-600 hover:text-indigo-600" wire:navigate>Courses</a>
                        @auth
                            <a href="{{ route('student.dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-indigo-600" wire:navigate>Dashboard</a>
                            <a href="{{ route('student.certificates') }}" class="text-sm font-medium text-gray-600 hover:text-indigo-600" wire:navigate>Certificates</a>
                        @endauth
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    @auth
                        <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-gray-600 hover:text-red-600">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-indigo-600" wire:navigate>Login</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" wire:navigate>Register</a>
                    @endguest
                </div>
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <main>
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="mt-16 border-t border-gray-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="text-center text-sm text-gray-500">
                &copy; {{ date('Y') }} Module LMS. Built with Laravel, Livewire & Filament.
            </div>
        </div>
    </footer>
</body>
</html>
