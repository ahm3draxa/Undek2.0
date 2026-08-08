<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::where('email', 'ceo@undek.com')->first();

        $services = [
            ['name' => 'Web Development', 'slug' => 'web-development', 'description' => 'Fast, custom sites and web apps — from WordPress to headless. Built to convert and easy to run.'],
            ['name' => 'SEO', 'slug' => 'seo', 'description' => 'Technical audits, content strategy and link building that climb rankings and stay there.'],
            ['name' => 'IT Technical Support', 'slug' => 'it-technical-support', 'description' => 'Round-the-clock monitoring, maintenance and fixes so your systems never miss a beat.'],
            ['name' => 'Graphic Designing', 'slug' => 'graphic-designing', 'description' => 'Brand identities, UI, print and social creative that make you unmistakable.'],
            ['name' => 'Video Editing', 'slug' => 'video-editing', 'description' => 'Reels, ads and explainers cut for attention — motion, sound and story that land.'],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(
                ['slug' => $service['slug']],
                $service + [
                    'icon' => Service::suggestIcon($service['name']),
                    'is_active' => true,
                    'created_by' => $creator?->id,
                ]
            );
        }
    }
}
