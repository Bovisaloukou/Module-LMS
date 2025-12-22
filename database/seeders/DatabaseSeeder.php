<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);
        $this->call(CategorySeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@lms.test',
        ]);
        $admin->assignRole('admin');

        $instructor = User::factory()->create([
            'name' => 'Instructor User',
            'email' => 'instructor@lms.test',
        ]);
        $instructor->assignRole('instructor');

        $student = User::factory()->create([
            'name' => 'Student User',
            'email' => 'student@lms.test',
        ]);
        $student->assignRole('student');

        $this->call(DemoCourseSeeder::class);
    }
}
