<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900">Browse Courses</h1>

    {{-- Filters --}}
    <div class="mt-6 flex flex-wrap items-center gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
        <div class="flex-1">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search courses..."
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <select wire:model.live="category" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->slug }}">{{ $cat->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="level" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">All Levels</option>
            @foreach($levels as $lvl)
                <option value="{{ $lvl->value }}">{{ $lvl->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="sort" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="latest">Latest</option>
            <option value="popular">Most Popular</option>
            <option value="rating">Highest Rated</option>
            <option value="price_low">Price: Low to High</option>
            <option value="price_high">Price: High to Low</option>
        </select>

        @if($search || $category || $level || $freeOnly || $sort !== 'latest')
            <button wire:click="clearFilters" class="text-sm font-medium text-red-600 hover:text-red-500">Clear</button>
        @endif
    </div>

    {{-- Results --}}
    <div class="mt-8">
        @if($courses->count())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($courses as $course)
                    @include('livewire.partials.course-card', ['course' => $course])
                @endforeach
            </div>

            <div class="mt-8">
                {{ $courses->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <p class="text-lg text-gray-500">No courses found matching your criteria.</p>
                <button wire:click="clearFilters" class="mt-4 text-sm font-medium text-indigo-600 hover:text-indigo-500">Clear filters</button>
            </div>
        @endif
    </div>
</div>
