<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function event(): Event
    {
        return Event::create([
            'slug' => 'idea-to-intelligent-system',
            'title' => 'From Idea to Intelligent System',
            'event_code' => 'SEM',
            'expected_amount' => 399.00,
            'starts_at' => '2026-09-05 14:00:00',
            'location' => 'Twinniz Cafe, Olongapo',
            'is_active' => true,
        ]);
    }

    public function test_step_one_creates_a_pending_registration(): void
    {
        $this->event();

        $response = $this->postJson('/api/registrations', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'phone' => '09171234567',
            'event' => 'idea-to-intelligent-system',
            'audience' => 'Student',
            'lead_source' => 'fb-ad-landing',
        ]);

        $response->assertCreated()
            ->assertJsonPath('event.expected_amount', 399)
            ->assertJsonPath('payment.gcash_number', '0928 320 7029');

        $this->assertMatchesRegularExpression(
            '/^ABBA-SEM-\d{4}-\d{4}$/',
            $response->json('registration_number'),
        );

        $this->assertDatabaseHas('registrations', [
            'email' => 'juan@example.com',
            'status' => RegistrationStatus::Pending->value,
        ]);
    }

    public function test_matching_payment_is_queued_for_verification(): void
    {
        Storage::fake('local');
        $event = $this->event();
        $registration = $this->makeRegistration($event);

        $response = $this->post("/api/registrations/{$registration->id}/payment", [
            'reference_number' => '1000123456789',
            'amount_submitted' => 399,
            'receipt' => UploadedFile::fake()->create('receipt.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('payment_status', PaymentStatus::ForVerification->value)
            ->assertJsonPath('flags.duplicate', false)
            ->assertJsonPath('flags.amount_mismatch', false);

        $payment = $registration->payments()->first();
        $this->assertNotNull($payment->receipt_path);
        Storage::disk('local')->assertExists($payment->receipt_path);
    }

    public function test_amount_mismatch_is_flagged(): void
    {
        Storage::fake('local');
        $event = $this->event();
        $registration = $this->makeRegistration($event);

        $response = $this->post("/api/registrations/{$registration->id}/payment", [
            'reference_number' => '2000123456789',
            'amount_submitted' => 300,
            'receipt' => UploadedFile::fake()->create('receipt.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('payment_status', PaymentStatus::AmountMismatch->value)
            ->assertJsonPath('flags.amount_mismatch', true);
    }

    public function test_duplicate_reference_is_flagged(): void
    {
        Storage::fake('local');
        $event = $this->event();
        $first = $this->makeRegistration($event);
        $second = $this->makeRegistration($event, 'maria@example.com');

        $this->post("/api/registrations/{$first->id}/payment", [
            'reference_number' => '3000123456789',
            'amount_submitted' => 399,
            'receipt' => UploadedFile::fake()->create('a.jpg', 100, 'image/jpeg'),
        ])->assertCreated();

        $this->post("/api/registrations/{$second->id}/payment", [
            'reference_number' => '3000123456789',
            'amount_submitted' => 399,
            'receipt' => UploadedFile::fake()->create('b.jpg', 100, 'image/jpeg'),
        ])
            ->assertCreated()
            ->assertJsonPath('payment_status', PaymentStatus::Duplicate->value)
            ->assertJsonPath('flags.duplicate', true);
    }

    public function test_status_lookup_returns_current_state(): void
    {
        $event = $this->event();
        $registration = $this->makeRegistration($event);

        $this->getJson("/api/registrations/{$registration->registration_number}")
            ->assertOk()
            ->assertJsonPath('status', RegistrationStatus::Pending->value);
    }

    protected function makeRegistration(Event $event, string $email = 'juan@example.com'): Registration
    {
        return Registration::create([
            'registration_number' => Registration::generateNumber($event),
            'name' => 'Juan Dela Cruz',
            'email' => $email,
            'event_id' => $event->id,
        ]);
    }
}
