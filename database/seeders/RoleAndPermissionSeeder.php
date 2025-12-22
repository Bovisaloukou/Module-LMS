<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'courses.view', 'courses.create', 'courses.update', 'courses.delete', 'courses.publish',
            'modules.view', 'modules.create', 'modules.update', 'modules.delete',
            'lessons.view', 'lessons.create', 'lessons.update', 'lessons.delete',
            'quizzes.view', 'quizzes.create', 'quizzes.update', 'quizzes.delete',
            'enrollments.view', 'enrollments.create', 'enrollments.refund',
            'payments.view', 'payments.refund',
            'certificates.view', 'certificates.generate',
            'reviews.view', 'reviews.create', 'reviews.update', 'reviews.delete', 'reviews.moderate',
            'discussions.view', 'discussions.create', 'discussions.moderate',
            'users.view', 'users.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        $instructor = Role::firstOrCreate(['name' => 'instructor']);
        $instructor->syncPermissions([
            'courses.view', 'courses.create', 'courses.update', 'courses.delete', 'courses.publish',
            'modules.view', 'modules.create', 'modules.update', 'modules.delete',
            'lessons.view', 'lessons.create', 'lessons.update', 'lessons.delete',
            'quizzes.view', 'quizzes.create', 'quizzes.update', 'quizzes.delete',
            'enrollments.view',
            'reviews.view',
            'discussions.view', 'discussions.create', 'discussions.moderate',
        ]);

        $student = Role::firstOrCreate(['name' => 'student']);
        $student->syncPermissions([
            'courses.view',
            'lessons.view',
            'quizzes.view',
            'enrollments.create',
            'reviews.view', 'reviews.create', 'reviews.update',
            'discussions.view', 'discussions.create',
            'certificates.view',
        ]);
    }
}
