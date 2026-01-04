<?php

namespace Tests\Feature\Livewire;

use App\Enums\CourseStatus;
use App\Livewire\Home;
use App\Models\Category;
use App\Models\Course;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_home_page_renders(): void
    {
        $this->get(route('home'))
            ->assertOk();
    }

    public function test_home_shows_published_courses(): void
    {
        $published = Course::factory()->create(['status' => CourseStatus::Published, 'published_at' => now()]);
        Course::factory()->create(['status' => CourseStatus::Draft]);

        Livewire::test(Home::class)
            ->assertSee($published->title);
    }

    public function test_home_shows_categories(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        Livewire::test(Home::class)
            ->assertSee($category->name);
    }
}
