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
        // Job titles per team, most senior first. Departments themselves (and
        // their tools) come from RolesAndPermissionsSeeder.
        $ladders = [
            'management' => ['CEO & Founder' => 100, 'Co-Founder' => 90, 'General Manager' => 50, 'Project Manager' => 40, 'Finance Manager' => 30, 'HR Manager' => 30],
            'web-development' => ['Team Lead' => 5, 'Senior Developer' => 4, 'Developer' => 3, 'Junior Developer' => 2, 'Web Development Intern' => 1],
            'seo-marketing' => ['Marketing Lead' => 3, 'SEO Specialist' => 2, 'Marketing Intern' => 1],
            'design-video' => ['UI/UX Designer' => 3, 'Graphic Designer' => 2, 'Video Editor' => 2, 'Design Intern' => 1],
            'it-support' => ['IT Support Specialist' => 4, 'Technical Specialist' => 3, 'Technical Assistant' => 2, 'IT Support Intern' => 1],
            'sales' => ['Account Manager' => 2, 'Sales Executive' => 1],
        ];

        foreach ($ladders as $departmentSlug => $positions) {
            $department = Department::where('slug', $departmentSlug)->first();

            foreach ($positions as $name => $rank) {
                Position::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'department_id' => $department?->id, 'rank' => $rank],
                );
            }
        }

        // Categories only group the skill list on screen — they grant nothing.
        foreach (['Backend', 'Frontend', 'Design', 'Marketing', 'DevOps', 'Management', 'Support', 'Video & Media'] as $name) {
            SkillCategory::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
