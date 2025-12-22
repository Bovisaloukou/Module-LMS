<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Web Development', 'slug' => 'web-development', 'icon' => 'heroicon-o-globe-alt', 'sort_order' => 0],
            ['name' => 'Mobile Development', 'slug' => 'mobile-development', 'icon' => 'heroicon-o-device-phone-mobile', 'sort_order' => 1],
            ['name' => 'Data Science', 'slug' => 'data-science', 'icon' => 'heroicon-o-chart-bar', 'sort_order' => 2],
            ['name' => 'DevOps', 'slug' => 'devops', 'icon' => 'heroicon-o-server-stack', 'sort_order' => 3],
            ['name' => 'Design', 'slug' => 'design', 'icon' => 'heroicon-o-paint-brush', 'sort_order' => 4],
            ['name' => 'Business', 'slug' => 'business', 'icon' => 'heroicon-o-briefcase', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
