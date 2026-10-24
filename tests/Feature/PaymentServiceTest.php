<?php

namespace Tests\Feature;

use App\Enums\EmailStatus;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function payment(): Payment
    {
        $event = Event::firstOrCreate(['slug' => 'idea-to-intelligent-system'], [
            'title' => 'From Idea to Intelligent System',
            'event_code' => 'SEM',
            'expected_amount' => 399.00,
            'starts_at' => '2026-09-05 14:00:00',
            'location' => 'Twinniz Cafe, Olongapo',
            'is_active' => true,
        ]);

        $registration = Registration::create([
            'registration_number' => Registration::generateNumber($event),
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'event_id' => $event->id,
        ]);

        return $registration->payments()->create([
            'payment_method' => 'gcash',
            'expected_amount' => 399.00,
            'amount_submitted' => 399.00,
            'reference_number' => '1000123456789',
            'status' => PaymentStatus::ForVerification,
        ]);
    }

    public function test_confirm_verifies_payment_confirms_registration_and_sends_email(): void
    {
        config()->set('n8n.payment_confirmed_url', 'https://n8n.test/webhook/registration-payment-confirmed');
        config()->set('n8n.payment_confirmed_token', 'secret');
        Http::fake(['n8n.test/*' => Http::response(['ok' => true], 200)]);

        $payment = $this->payment();
        $admin = User::factory()->create(['email' => 'admin@abbadev.com']);

        app(PaymentService::class)->confirm($payment, $admin, 'Matched in GCash portal');

        $payment->refresh();
        $this->assertSame(PaymentStatus::Verified, $payment->status);
        $this->assertSame(EmailStatus::Sent, $payment->email_status);
        $this->assertSame($admin->id, $payment->verified_by);
        $this->assertNotNull($payment->verified_at);
        $this->assertNotNull($payment->confirmation_email_sent_at);
        $this->assertSame(RegistrationStatus::Confirmed, $payment->registration->status);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer secret')
            && $request['event'] === 'registration.payment_confirmed'
            && $request['registration_number'] === $payment->registration->registration_number);
    }

    public function test_confirm_marks_email_failed_when_webhook_unreachable(): void
    {
        config()->set('n8n.payment_confirmed_url', 'https://n8n.test/webhook/x');
        Http::fake(['n8n.test/*' => Http::response('error', 500)]);

        $payment = $this->payment();
        $admin = User::factory()->create(['email' => 'admin@abbadev.com']);

        app(PaymentService::class)->confirm($payment, $admin);

        $payment->refresh();
        $this->assertSame(PaymentStatus::Verified, $payment->status);
        $this->assertSame(EmailStatus::Failed, $payment->email_status);
        $this->assertSame(RegistrationStatus::Confirmed, $payment->registration->status);
    }

    public function test_set_status_rejects_without_confirming_registration(): void
    {
        $payment = $this->payment();
        $admin = User::factory()->create(['email' => 'admin@abbadev.com']);

        app(PaymentService::class)->setStatus($payment, PaymentStatus::Rejected, $admin, 'No matching transaction');

        $payment->refresh();
        $this->assertSame(PaymentStatus::Rejected, $payment->status);
        $this->assertSame(RegistrationStatus::Pending, $payment->registration->status);
        $this->assertSame(EmailStatus::NotSent, $payment->email_status);
    }

    public function test_confirm_many_only_confirms_payments_awaiting_verification(): void
    {
        $service = app(PaymentService::class);
        $admin = User::factory()->create(['email' => 'admin@abbadev.com']);

        $ok = $this->payment();
        $mismatch = $this->payment();
        $mismatch->update(['status' => PaymentStatus::AmountMismatch]);
        $verified = $this->payment();
        $verified->update(['status' => PaymentStatus::Verified]);

        $result = $service->confirmMany([$ok, $mismatch, $verified], $admin, 'batch');

        $this->assertCount(1, $result['confirmed']);
        $this->assertSame(2, $result['skipped']);
        $this->assertSame(PaymentStatus::Verified, $ok->refresh()->status);
        $this->assertSame(RegistrationStatus::Confirmed, $ok->registration->status);
        $this->assertSame(PaymentStatus::AmountMismatch, $mismatch->refresh()->status);
        $this->assertSame(RegistrationStatus::Pending, $mismatch->registration->status);
    }

    public function test_send_confirmation_emails_skips_already_sent_and_unverified(): void
    {
        config()->set('n8n.payment_confirmed_url', 'https://n8n.test/webhook/x');
        Http::fake(['n8n.test/*' => Http::response(['ok' => true], 200)]);

        $service = app(PaymentService::class);
        $failed = $this->payment();
        $failed->update(['status' => PaymentStatus::Verified, 'email_status' => EmailStatus::Failed]);
        $sent = $this->payment();
        $sent->update(['status' => PaymentStatus::Verified, 'email_status' => EmailStatus::Sent]);
        $pending = $this->payment();

        $result = $service->sendConfirmationEmails([$failed, $sent, $pending]);

        $this->assertSame(['sent' => 1, 'failed' => 0, 'skipped' => 2], $result);
        $this->assertSame(EmailStatus::Sent, $failed->refresh()->email_status);
        Http::assertSentCount(1);
    }
}
