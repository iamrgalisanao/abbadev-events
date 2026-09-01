<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'slug' => 'idea-to-intelligent-system',
                'title' => 'From Idea to Intelligent System',
                'type' => 'Seminar',
                'mode' => 'In-person',
                'event_code' => 'SEM',
                'expected_amount' => 399.00,
                'price_label' => '₱399',
                'starts_at' => '2026-09-05 14:00:00',
                'duration' => '3 hours',
                'level' => 'Beginner',
                'location' => 'Twinniz Cafe, Olongapo',
                'audience' => ['Students', 'SME owners'],
                'blurb' => 'Transform ideas into intelligent systems using AI, modern software development, and structured project delivery - the practical tools and best practices that turn concepts into real, measurable impact.',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'first-chatbot',
                'title' => 'Build Your First AI Chatbot',
                'type' => 'Workshop',
                'mode' => 'Online',
                'event_code' => 'WRK',
                'expected_amount' => 750.00,
                'price_label' => '₱750',
                'starts_at' => '2026-10-08 10:00:00',
                'duration' => '3 hours',
                'level' => 'Hands-on',
                'location' => null,
                'audience' => ['Students', 'SME owners'],
                'blurb' => 'A hands-on session building and deploying a working chatbot from scratch - no prior AI experience required.',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'digital-transformation-smes',
                'title' => 'Digital Transformation for SMEs',
                'type' => 'Seminar',
                'mode' => 'In-person',
                'event_code' => 'SEM',
                'expected_amount' => 1200.00,
                'price_label' => '₱1,200',
                'starts_at' => '2026-11-05 13:00:00',
                'duration' => 'Half day',
                'level' => 'Intermediate',
                'location' => 'Metro Manila',
                'audience' => ['SME owners'],
                'blurb' => 'Move from spreadsheets and manual steps to connected systems - a practical roadmap you can adopt in phases.',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'intro-software-dev',
                'title' => 'Intro to Software Development',
                'type' => 'Seminar',
                'mode' => 'In-person',
                'event_code' => 'SEM',
                'expected_amount' => 0,
                'price_label' => 'Free',
                'starts_at' => '2026-10-18 09:00:00',
                'duration' => 'Half day',
                'level' => 'Beginner',
                'location' => 'Metro Manila',
                'audience' => ['Students'],
                'blurb' => 'How real software gets built - languages, tools, and the path from idea to shipped app - for students exploring a tech career.',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
            ],
            [
                'slug' => 'no-code-automation',
                'title' => 'No-Code Automation with n8n',
                'type' => 'Workshop',
                'mode' => 'Online',
                'event_code' => 'WRK',
                'expected_amount' => 750.00,
                'price_label' => '₱750',
                'starts_at' => '2026-11-19 14:00:00',
                'duration' => '3 hours',
                'level' => 'Hands-on',
                'location' => null,
                'audience' => ['Students', 'SME owners'],
                'blurb' => 'Connect apps and automate approvals, notifications, and data entry visually - build a real working workflow live.',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 5,
            ],
            [
                'slug' => 'project-management',
                'title' => 'Project Management Fundamentals',
                'type' => 'Webinar',
                'mode' => 'Online',
                'event_code' => 'WEB',
                'expected_amount' => 0,
                'price_label' => 'Free',
                'starts_at' => '2026-12-03 15:00:00',
                'duration' => '2 hours',
                'level' => 'Beginner',
                'location' => null,
                'audience' => ['Students', 'SME owners'],
                'blurb' => 'Scope, planning, and delivery basics that keep technology projects on track - for aspiring PMs and owners alike.',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 6,
            ],
        ];

        foreach ($events as $event) {
            Event::updateOrCreate(['slug' => $event['slug']], $event);
        }
    }
}
