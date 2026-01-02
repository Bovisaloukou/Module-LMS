<div class="flex min-h-[calc(100vh-12rem)] items-center justify-center px-4">
    <div class="w-full max-w-md">
        <h1 class="mb-8 text-center text-3xl font-bold text-gray-900">Create Account</h1>

        <form wire:submit="register" class="space-y-5 rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-200">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Name</label>
                <input wire:model="name" id="name" type="text" autocomplete="name"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                <input wire:model="email" id="email" type="email" autocomplete="email"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                <input wire:model="password" id="password" type="password"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Confirm Password</label>
                <input wire:model="password_confirmation" id="password_confirmation" type="password"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                <span wire:loading.remove wire:target="register">Create Account</span>
                <span wire:loading wire:target="register">Creating account...</span>
            </button>

            <p class="text-center text-sm text-gray-600">
                Already have an account? <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500" wire:navigate>Sign In</a>
            </p>
        </form>
    </div>
</div>
