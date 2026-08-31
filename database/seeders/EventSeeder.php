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
                'event_code' => 'SEM',
                'expected_amount' => 399.00,
                'starts_at' => '2026-09-05 14:00:00',
                'location' => 'Twinniz Cafe, Olongapo',
                'is_active' => true,
            ],
        ];

        foreach ($events as $event) {
            Event::updateOrCreate(['slug' => $event['slug']], $event);
        }
    }
}
