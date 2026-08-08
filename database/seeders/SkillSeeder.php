<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skillsByCategory = [
            'Backend' => ['PHP', 'Laravel', 'Node.js', 'Python', 'MySQL', 'PostgreSQL', 'REST APIs', 'WordPress'],
            'Frontend' => ['JavaScript', 'Vue.js', 'React', 'Tailwind CSS', 'HTML/CSS', 'Alpine.js'],
            'Design' => ['Figma', 'Adobe Photoshop', 'Adobe Illustrator', 'UI/UX Design', 'Branding & Identity', 'Print Design'],
            'Marketing' => ['SEO', 'Google Ads', 'Content Marketing', 'Email Marketing', 'Social Media Marketing', 'Copywriting'],
            'DevOps' => ['Docker', 'AWS', 'CI/CD', 'Linux Server Administration', 'Nginx / Apache'],
            'Management' => ['Project Management', 'Agile / Scrum', 'Client Communication', 'Team Leadership'],
            'Support' => ['Technical Support', 'Network Administration', 'Troubleshooting', 'Help Desk'],
            'Video & Media' => ['Video Editing', 'Adobe Premiere Pro', 'After Effects', 'Motion Graphics', 'Color Grading'],
        ];

        $skills = [];

        foreach ($skillsByCategory as $categoryName => $names) {
            $category = SkillCategory::where('name', $categoryName)->first();

            foreach ($names as $name) {
                $skills[$name] = Skill::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'category_id' => $category?->id]
                );
            }
        }

        $serviceSkillMap = [
            'web-development' => ['PHP', 'Laravel', 'JavaScript', 'Vue.js', 'React', 'Tailwind CSS', 'HTML/CSS', 'MySQL', 'REST APIs', 'WordPress'],
            'seo' => ['SEO', 'Content Marketing', 'Copywriting', 'Google Ads'],
            'it-technical-support' => ['Technical Support', 'Network Administration', 'Troubleshooting', 'Help Desk', 'Linux Server Administration'],
            'graphic-designing' => ['Figma', 'Adobe Photoshop', 'Adobe Illustrator', 'UI/UX Design', 'Branding & Identity', 'Print Design'],
            'video-editing' => ['Video Editing', 'Adobe Premiere Pro', 'After Effects', 'Motion Graphics', 'Color Grading'],
        ];

        foreach ($serviceSkillMap as $slug => $names) {
            $service = Service::where('slug', $slug)->first();

            if ($service) {
                $ids = collect($names)->map(fn ($name) => $skills[$name]?->id)->filter()->values();
                $service->skills()->sync($ids);
            }
        }
    }
}
