<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use Database\Seeders\EventSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_endpoint_lists_active_events_in_order(): void
    {
        $this->seed(EventSeeder::class);

        $this->getJson('/api/events')
            ->assertOk()
            ->assertJsonCount(6, 'events')
            ->assertJsonPath('events.0.slug', 'idea-to-intelligent-system')
            ->assertJsonPath('events.0.is_free', false)
            ->assertJsonPath('events.0.price_label', '₱399');
    }

    public function test_featured_returns_at_most_three(): void
    {
        $this->seed(EventSeeder::class);

        $response = $this->getJson('/api/events?featured=1')->assertOk();

        $this->assertCount(3, $response->json('events'));
        foreach ($response->json('events') as $event) {
            $this->assertTrue($event['is_featured']);
        }
    }

    public function test_inactive_events_are_hidden(): void
    {
        $this->seed(EventSeeder::class);
        Event::where('slug', 'first-chatbot')->update(['is_active' => false]);

        $slugs = collect($this->getJson('/api/events')->json('events'))->pluck('slug');

        $this->assertFalse($slugs->contains('first-chatbot'));
    }

    public function test_free_event_registration_confirms_without_payment(): void
    {
        $event = Event::create([
            'slug' => 'free-webinar',
            'title' => 'Free Webinar',
            'type' => 'Webinar',
            'mode' => 'Online',
            'event_code' => 'WEB',
            'expected_amount' => 0,
            'price_label' => 'Free',
            'starts_at' => '2026-12-03 15:00:00',
            'is_active' => true,
        ]);

        $this->postJson('/api/registrations', [
            'name' => 'Free Joe',
            'email' => 'free@example.com',
            'event' => $event->slug,
        ])
            ->assertCreated()
            ->assertJsonPath('requires_payment', false)
            ->assertJsonPath('payment', null);

        $this->assertDatabaseHas('registrations', [
            'email' => 'free@example.com',
            'status' => RegistrationStatus::Confirmed->value,
        ]);
    }
}
