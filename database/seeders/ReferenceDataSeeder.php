<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use App\Models\SkillCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'Executive', 'Engineering', 'Design', 'Marketing', 'Sales',
            'Operations', 'Finance', 'Human Resources', 'Customer Support',
        ];

        foreach ($departments as $name) {
            Department::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }

        $positions = [
            'Chief Executive Officer', 'General Manager', 'Project Manager',
            'Team Lead', 'Senior Developer', 'Developer', 'Junior Developer',
            'UI/UX Designer', 'Graphic Designer', 'SEO Specialist',
            'IT Support Specialist', 'Video Editor', 'Account Manager',
            'HR Manager', 'Finance Manager', 'Intern',
        ];

        foreach ($positions as $name) {
            Position::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }

        $categories = [
            'Backend', 'Frontend', 'Design', 'Marketing', 'DevOps',
            'Management', 'Support', 'Video & Media',
        ];

        foreach ($categories as $name) {
            SkillCategory::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
