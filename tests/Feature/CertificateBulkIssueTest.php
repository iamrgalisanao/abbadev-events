<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Filament\Pages\CertificateGenerator;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CertificateBulkIssueTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        // Some tests run two batches, so this is reused rather than recreated.
        return User::firstOrCreate(
            ['email' => 'admin@abbadev.com'],
            ['name' => 'Admin', 'password' => 'password'],
        );
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

    protected function payment(Registration $registration, PaymentStatus $status): Payment
    {
        return Payment::create([
            'registration_id' => $registration->id,
            'expected_amount' => 399,
            'amount_submitted' => 399,
            'reference_number' => fake()->unique()->numerify('##########'),
            'status' => $status->value,
        ]);
    }

    protected function attendee(Event $event, string $name, string $email, string $status = 'confirmed'): Registration
    {
        return Registration::create([
            'registration_number' => 'ABBA-WEB-2026-'.fake()->unique()->numerify('####'),
            'name' => $name,
            'email' => $email,
            'event_id' => $event->id,
            'status' => $status,
        ]);
    }

    protected function generator(Event $event, array $state = [])
    {
        return Livewire::actingAs($this->admin())
            ->test(CertificateGenerator::class)
            ->set('data.event_id', $event->id)
            ->set('data.certificate_title', 'Certificate of Participation')
            ->set('data.signatory_one_name', 'Ren Adreian Piad')
            ->when(
                $state !== [],
                function ($test) use ($state) {
                    foreach ($state as $key => $value) {
                        $test->set("data.{$key}", $value);
                    }

                    return $test;
                },
            );
    }

    public function test_it_issues_one_certificate_per_confirmed_attendee(): void
    {
        $event = $this->event();
        $this->attendee($event, 'Raizel D Galisanao', 'raizel@example.com');
        $this->attendee($event, 'Carl Jethro Babiano', 'carl@example.com');

        $this->generator($event)->call('bulkIssue', false);

        $this->assertSame(2, Certificate::count());
        $this->assertEqualsCanonicalizing(
            ['Raizel D Galisanao', 'Carl Jethro Babiano'],
            Certificate::pluck('recipient_name')->all(),
        );

        // Each one gets its own credential, so each QR resolves separately.
        $this->assertCount(2, Certificate::pluck('credential_id')->unique());

        // The copy in the form is carried onto every certificate.
        $this->assertSame(
            ['Ren Adreian Piad', 'Ren Adreian Piad'],
            Certificate::pluck('signatory_one_name')->all(),
        );
    }

    public function test_unconfirmed_registrations_are_excluded_unless_asked_for(): void
    {
        $event = $this->event();
        $this->attendee($event, 'Raizel D Galisanao', 'raizel@example.com');
        $this->attendee($event, 'Franz Jeric Borbo', 'franz@example.com', RegistrationStatus::Pending->value);

        $this->generator($event)->call('bulkIssue', false);
        $this->assertSame(['Raizel D Galisanao'], Certificate::pluck('recipient_name')->all());

        $this->generator($event)->call('bulkIssue', true);
        $this->assertEqualsCanonicalizing(
            ['Raizel D Galisanao', 'Franz Jeric Borbo'],
            Certificate::pluck('recipient_name')->all(),
        );
    }

    public function test_a_verified_payment_carries_an_unconfirmed_registration_into_the_batch(): void
    {
        $event = $this->event();
        $paid = $this->attendee($event, 'Franz Jeric Borbo', 'franz@example.com', RegistrationStatus::Pending->value);
        $this->payment($paid, PaymentStatus::Verified);

        // The default batch, without asking for unconfirmed registrations.
        $this->generator($event)->call('bulkIssue', false);

        $this->assertSame(1, Certificate::count());
        $this->assertSame($paid->id, Certificate::first()->registration_id);
    }

    public function test_an_unpaid_unconfirmed_registration_is_still_left_out(): void
    {
        $event = $this->event();
        $nothing = $this->attendee($event, 'Franz Jeric Borbo', 'franz@example.com', RegistrationStatus::Pending->value);
        $rejected = $this->attendee($event, 'John Lourence Lingad', 'lourence@example.com', RegistrationStatus::Pending->value);
        $awaiting = $this->attendee($event, 'Mark Andrei Pascua', 'mark@example.com', RegistrationStatus::Pending->value);
        $this->payment($rejected, PaymentStatus::Rejected);
        $this->payment($awaiting, PaymentStatus::ForVerification);

        $this->generator($event)->call('bulkIssue', false);

        $this->assertSame(0, Certificate::count());
        $this->assertNotNull($nothing->fresh());
    }

    public function test_repeat_signups_on_one_email_get_a_single_certificate(): void
    {
        $event = $this->event();
        // Pending signs up first, so only the status rule can pick the winner.
        $this->attendee($event, 'Franz Jeric Borbo', 'franz@example.com', RegistrationStatus::Pending->value);
        $confirmed = $this->attendee($event, 'Franz Jeric Borbo', 'franz@example.com');

        $this->generator($event)->call('bulkIssue', true);

        $this->assertSame(1, Certificate::count());
        // The confirmed row wins, so the certificate hangs off the real one.
        $this->assertSame($confirmed->id, Certificate::first()->registration_id);
    }

    public function test_the_registration_with_a_verified_payment_wins_a_duplicate(): void
    {
        $event = $this->event();

        // Both rows reached confirmed, but only one has money checked against
        // it. Without the payment rule the earlier row would have won.
        $unpaid = $this->attendee($event, 'Franz Jeric Borbo', 'franz@example.com');
        $paid = $this->attendee($event, 'Franz Jeric Borbo', 'franz@example.com');
        $this->payment($unpaid, PaymentStatus::ForVerification);
        $this->payment($paid, PaymentStatus::Verified);

        $this->generator($event)->call('bulkIssue', false);

        $this->assertSame(1, Certificate::count());
        $this->assertSame($paid->id, Certificate::first()->registration_id);
    }

    public function test_a_verified_payment_outranks_a_confirmed_registration(): void
    {
        $event = $this->event();

        $confirmedButUnpaid = $this->attendee($event, 'Franz Jeric Borbo', 'franz@example.com');
        $paidButPending = $this->attendee($event, 'Franz Jeric Borbo', 'franz@example.com', RegistrationStatus::Pending->value);
        $this->payment($paidButPending, PaymentStatus::Verified);

        $this->generator($event)->call('bulkIssue', true);

        $this->assertSame(1, Certificate::count());
        $this->assertSame($paidButPending->id, Certificate::first()->registration_id);
        $this->assertNotSame($confirmedButUnpaid->id, Certificate::first()->registration_id);
    }

    public function test_a_rejected_payment_does_not_win_a_duplicate(): void
    {
        $event = $this->event();

        // The rejected row is the later sign-up, so if anything other than a
        // verified payment jumped the queue it would win here.
        $clean = $this->attendee($event, 'Franz Jeric Borbo', 'franz@example.com');
        $rejected = $this->attendee($event, 'Franz Jeric Borbo', 'franz@example.com');
        $this->payment($rejected, PaymentStatus::Rejected);

        $this->generator($event)->call('bulkIssue', false);

        $this->assertSame(1, Certificate::count());
        $this->assertSame($clean->id, Certificate::first()->registration_id);
    }

    public function test_a_second_run_tops_up_instead_of_reissuing(): void
    {
        $event = $this->event();
        $first = $this->attendee($event, 'Raizel D Galisanao', 'raizel@example.com');

        $this->generator($event)->call('bulkIssue', false);
        $existing = Certificate::firstWhere('registration_id', $first->id);

        // A late registration arrives after the first batch.
        $this->attendee($event, 'Carl Jethro Babiano', 'carl@example.com');

        $this->generator($event)->call('bulkIssue', false);

        $this->assertSame(2, Certificate::count());

        // The certificate issued in the first run is untouched - same row,
        // same credential ID, so the QR already handed out still resolves.
        $existing->refresh();
        $this->assertSame($existing->credential_id, Certificate::find($existing->id)->credential_id);
        $this->assertTrue($existing->updated_at->equalTo($existing->getOriginal('updated_at')));
    }

    public function test_it_never_writes_to_registrations_or_sessions(): void
    {
        $event = $this->event();
        $attendee = $this->attendee($event, 'Raizel D Galisanao', 'raizel@example.com');

        $registrationBefore = $attendee->fresh()->getAttributes();
        $eventBefore = $event->fresh()->getAttributes();

        $this->generator($event)->call('bulkIssue', true);

        $this->assertSame($registrationBefore, $attendee->fresh()->getAttributes());
        $this->assertSame($eventBefore, $event->fresh()->getAttributes());
        $this->assertSame(1, Registration::count());
        $this->assertSame(1, Event::count());
    }

    public function test_a_batch_without_a_session_writes_nothing(): void
    {
        $event = $this->event();
        $this->attendee($event, 'Raizel D Galisanao', 'raizel@example.com');

        Livewire::actingAs($this->admin())
            ->test(CertificateGenerator::class)
            ->set('data.certificate_title', 'Certificate of Participation')
            ->call('bulkIssue', false);

        $this->assertSame(0, Certificate::count());
    }

    public function test_a_batch_without_a_title_writes_nothing(): void
    {
        $event = $this->event();
        $this->attendee($event, 'Raizel D Galisanao', 'raizel@example.com');

        Livewire::actingAs($this->admin())
            ->test(CertificateGenerator::class)
            ->set('data.event_id', $event->id)
            ->set('data.certificate_title', '')
            ->call('bulkIssue', false);

        $this->assertSame(0, Certificate::count());
    }

    public function test_the_summary_spells_out_what_the_batch_will_do(): void
    {
        $event = $this->event();
        $this->attendee($event, 'Raizel D Galisanao', 'raizel@example.com');
        $this->attendee($event, 'Carl Jethro Babiano', 'carl@example.com');
        $this->attendee($event, 'Carl Jethro Babiano', 'carl@example.com');
        $this->attendee($event, 'Franz Jeric Borbo', 'franz@example.com', RegistrationStatus::Pending->value);

        $summary = implode("\n", app(CertificateGenerator::class)
            ->tap(fn (CertificateGenerator $page) => $page->data = ['event_id' => $event->id])
            ->bulkSummary(false));

        $this->assertStringContainsString('3 registrations (confirmed, or with a verified payment)', $summary);
        $this->assertStringContainsString('1 duplicate registration for the same email will be counted once', $summary);
        $this->assertStringContainsString('Creates 2 certificates', $summary);
        $this->assertStringContainsString('No registration or session record is modified', $summary);
    }

    public function test_the_summary_reports_attendees_that_will_be_skipped(): void
    {
        $event = $this->event();
        $issued = $this->attendee($event, 'Raizel D Galisanao', 'raizel@example.com');
        $this->attendee($event, 'Carl Jethro Babiano', 'carl@example.com');

        Certificate::create([
            'credential_id' => 'aa11bb22cc33dd44ee55ff6677889900',
            'recipient_name' => $issued->name,
            'certificate_title' => 'Certificate of Participation',
            'registration_id' => $issued->id,
        ]);

        $summary = implode("\n", app(CertificateGenerator::class)
            ->tap(fn (CertificateGenerator $page) => $page->data = ['event_id' => $event->id])
            ->bulkSummary(false));

        $this->assertStringContainsString('1 already hold a certificate and will be skipped', $summary);
        $this->assertStringContainsString('Creates 1 certificate,', $summary);
    }
}
