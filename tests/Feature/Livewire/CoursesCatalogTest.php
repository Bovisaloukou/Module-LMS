<?php

namespace Tests\Feature\Livewire;

use App\Enums\CourseStatus;
use App\Livewire\CoursesCatalog;
use App\Models\Category;
use App\Models\Course;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CoursesCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_catalog_page_renders(): void
    {
        $this->get(route('courses.catalog'))
            ->assertOk();
    }

    public function test_catalog_shows_published_courses(): void
    {
        $published = Course::factory()->create(['status' => CourseStatus::Published, 'published_at' => now()]);
        $draft = Course::factory()->create(['status' => CourseStatus::Draft]);

        Livewire::test(CoursesCatalog::class)
            ->assertSee($published->title)
            ->assertDontSee($draft->title);
    }

    public function test_catalog_can_search_courses(): void
    {
        Course::factory()->create(['title' => 'Laravel Mastery', 'status' => CourseStatus::Published, 'published_at' => now()]);
        Course::factory()->create(['title' => 'Vue.js Basics', 'status' => CourseStatus::Published, 'published_at' => now()]);

        Livewire::test(CoursesCatalog::class)
            ->set('search', 'Laravel')
            ->assertSee('Laravel Mastery')
            ->assertDontSee('Vue.js Basics');
    }

    public function test_catalog_can_filter_by_category(): void
    {
        $php = Category::factory()->create(['name' => 'PHP', 'slug' => 'php']);
        $js = Category::factory()->create(['name' => 'JavaScript', 'slug' => 'javascript']);

        Course::factory()->create(['title' => 'PHP Course', 'category_id' => $php->id, 'status' => CourseStatus::Published, 'published_at' => now()]);
        Course::factory()->create(['title' => 'JS Course', 'category_id' => $js->id, 'status' => CourseStatus::Published, 'published_at' => now()]);

        Livewire::test(CoursesCatalog::class)
            ->set('category', 'php')
            ->assertSee('PHP Course')
            ->assertDontSee('JS Course');
    }

    public function test_catalog_can_filter_by_level(): void
    {
        Course::factory()->create(['title' => 'Beginner Course', 'level' => 'beginner', 'status' => CourseStatus::Published, 'published_at' => now()]);
        Course::factory()->create(['title' => 'Advanced Course', 'level' => 'advanced', 'status' => CourseStatus::Published, 'published_at' => now()]);

        Livewire::test(CoursesCatalog::class)
            ->set('level', 'beginner')
            ->assertSee('Beginner Course')
            ->assertDontSee('Advanced Course');
    }

    public function test_catalog_can_clear_filters(): void
    {
        Course::factory()->create(['title' => 'Any Course', 'status' => CourseStatus::Published, 'published_at' => now()]);

        Livewire::test(CoursesCatalog::class)
            ->set('search', 'something')
            ->set('level', 'advanced')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('level', '')
            ->assertSet('category', '');
    }
}
