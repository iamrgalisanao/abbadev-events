<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Filament\Pages\CertificateGenerator;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CertificateGeneratorSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@abbadev.com',
            'password' => 'password',
        ]);
    }

    protected function event(array $overrides = []): Event
    {
        return Event::create([
            'slug' => 'web-dev-101-'.fake()->unique()->numerify('####'),
            'title' => 'Web Dev 101: Go Live with Vercel',
            'type' => 'Webinar',
            'mode' => 'Online',
            'event_code' => 'WEB',
            'expected_amount' => 0,
            'starts_at' => now()->subDays(13),
            'duration' => '2 Hours',
            'is_active' => true,
            ...$overrides,
        ]);
    }

    protected function attendee(Event $event, string $name, array $overrides = []): Registration
    {
        return Registration::create([
            'registration_number' => 'ABBA-WEB-2026-'.fake()->unique()->numerify('####'),
            'name' => $name,
            'email' => str($name)->slug().'@example.com',
            'event_id' => $event->id,
            'status' => RegistrationStatus::Confirmed->value,
            ...$overrides,
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function optionsOf(string $key, $test): array
    {
        $options = [];

        $test->assertSchemaComponentExists($key, 'form', function (Select $component) use (&$options): bool {
            $options = $component->getOptions();

            return true;
        });

        return $options;
    }

    public function test_only_sessions_that_have_already_run_are_offered(): void
    {
        $past = $this->event(['title' => 'Web Dev 101: Go Live with Vercel']);
        $upcoming = $this->event(['title' => 'Upcoming AI Lab', 'starts_at' => now()->addDays(20)]);

        $test = Livewire::actingAs($this->admin())->test(CertificateGenerator::class);
        $options = $this->optionsOf('event_id', $test);

        $this->assertArrayHasKey($past->id, $options);
        $this->assertArrayNotHasKey($upcoming->id, $options);
        $this->assertStringContainsString($past->starts_at->format('M j, Y'), $options[$past->id]);
    }

    public function test_a_session_that_has_been_deactivated_is_still_offered(): void
    {
        // Sessions are commonly switched off on the public site once they have
        // run, but their attendees still need certificates.
        $past = $this->event(['is_active' => false]);

        $test = Livewire::actingAs($this->admin())->test(CertificateGenerator::class);

        $this->assertArrayHasKey($past->id, $this->optionsOf('event_id', $test));
    }

    public function test_attendees_are_scoped_to_the_chosen_session(): void
    {
        $chosen = $this->event();
        $other = $this->event(['title' => 'From Idea to Intelligent System']);

        $ours = $this->attendee($chosen, 'Raizel D Galisanao');
        $theirs = $this->attendee($other, 'Mark Andrei Pascua');

        $test = Livewire::actingAs($this->admin())->test(CertificateGenerator::class);

        $this->assertSame([], $this->optionsOf('registration_id', $test));

        $test->set('data.event_id', $chosen->id);
        $options = $this->optionsOf('registration_id', $test);

        $this->assertArrayHasKey($ours->id, $options);
        $this->assertArrayNotHasKey($theirs->id, $options);
    }

    public function test_an_unconfirmed_attendee_is_flagged_rather_than_hidden(): void
    {
        $event = $this->event();
        $pending = $this->attendee($event, 'Franz Jeric Borbo', [
            'status' => RegistrationStatus::Pending->value,
        ]);

        $test = Livewire::actingAs($this->admin())
            ->test(CertificateGenerator::class)
            ->set('data.event_id', $event->id);

        $options = $this->optionsOf('registration_id', $test);

        $this->assertArrayHasKey($pending->id, $options);
        $this->assertStringContainsString('(Pending)', $options[$pending->id]);
    }

    public function test_picking_a_session_fills_the_session_fields(): void
    {
        $event = $this->event();

        Livewire::actingAs($this->admin())
            ->test(CertificateGenerator::class)
            ->set('data.event_id', $event->id)
            ->assertSchemaStateSet([
                'activity_title' => 'Web Dev 101: Go Live with Vercel',
                'activity_label' => 'for participating in the webinar',
                'duration' => '2 Hours',
            ], schema: 'form');
    }

    public function test_picking_an_attendee_fills_the_recipient_name(): void
    {
        $event = $this->event();
        $attendee = $this->attendee($event, 'Raizel D Galisanao');

        Livewire::actingAs($this->admin())
            ->test(CertificateGenerator::class)
            ->set('data.event_id', $event->id)
            ->set('data.registration_id', $attendee->id)
            ->assertSchemaStateSet(['recipient_name' => 'Raizel D Galisanao'], schema: 'form');
    }

    public function test_switching_session_clears_the_attendee(): void
    {
        $first = $this->event();
        $second = $this->event(['title' => 'From Idea to Intelligent System', 'type' => 'Seminar']);
        $attendee = $this->attendee($first, 'Raizel D Galisanao');

        Livewire::actingAs($this->admin())
            ->test(CertificateGenerator::class)
            ->set('data.event_id', $first->id)
            ->set('data.registration_id', $attendee->id)
            ->set('data.event_id', $second->id)
            ->assertSchemaStateSet([
                'registration_id' => null,
                'activity_title' => 'From Idea to Intelligent System',
                'activity_label' => 'for participating in the seminar',
            ], schema: 'form');
    }
}
