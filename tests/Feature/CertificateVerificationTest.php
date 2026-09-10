<?php

namespace Tests\Feature;

use App\Enums\CertificateStatus;
use App\Models\Certificate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function certificate(array $overrides = []): Certificate
    {
        return Certificate::create([
            'credential_id' => 'aa11bb22cc33dd44ee55ff6677889900',
            'recipient_name' => 'Raizel D. Galisanao',
            'certificate_title' => 'Certificate of Participation',
            'activity_title' => 'Web Dev 101: Go Live with Vercel',
            'organization_name' => 'ABBADEV',
            'tagline' => 'IT SOLUTIONS',
            'issued_on' => '2026-08-28',
            'duration' => '2 Hours',
            ...$overrides,
        ]);
    }

    public function test_verification_page_renders_the_issued_certificate(): void
    {
        $certificate = $this->certificate();

        $this->get("/verify/{$certificate->credential_id}")
            ->assertOk()
            ->assertSee('Raizel D. Galisanao')
            ->assertSee('Web Dev 101: Go Live with Vercel')
            ->assertSee('Verify authenticity');
    }

    public function test_the_page_embeds_a_qr_pointing_back_at_itself(): void
    {
        $certificate = $this->certificate();

        $this->get("/verify/{$certificate->credential_id}")
            ->assertOk()
            ->assertSee('ecert-qr-svg', escape: false)
            ->assertSee('Scan to verify');
    }

    public function test_the_stored_tagline_prints_under_the_wordmark(): void
    {
        $certificate = $this->certificate();

        $this->get("/verify/{$certificate->credential_id}")
            ->assertOk()
            ->assertSee('IT SOLUTIONS')
            ->assertSee('<span class="ecert-tagline">', escape: false);
    }

    public function test_a_certificate_issued_without_a_tagline_prints_none(): void
    {
        // Older records predate the field, so the lockup has to cope with null.
        $certificate = $this->certificate(['tagline' => null]);

        $this->get("/verify/{$certificate->credential_id}")
            ->assertOk()
            // The class name still appears in the stylesheet, so this looks
            // for the element rather than the string.
            ->assertDontSee('<span class="ecert-tagline">', escape: false)
            ->assertSee('ABBADEV');
    }

    public function test_an_unknown_credential_is_not_found(): void
    {
        $this->get('/verify/does-not-exist')
            ->assertNotFound()
            ->assertSee('Credential not found');

        $this->getJson('/verify/does-not-exist/checks')
            ->assertNotFound()
            ->assertJsonPath('valid', false);
    }

    public function test_checks_pass_for_an_active_certificate(): void
    {
        $certificate = $this->certificate();

        $this->getJson("/verify/{$certificate->credential_id}/checks")
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonCount(4, 'checks')
            ->assertJsonPath('checks.0.detail', 'The owner of this credential is Raizel D. Galisanao.')
            ->assertJsonPath('checks.1.detail', 'The issuer of this credential is ABBADEV.')
            ->assertJsonPath('checks.3.passed', true);
    }

    public function test_a_revoked_certificate_fails_the_final_check(): void
    {
        $certificate = $this->certificate([
            'status' => CertificateStatus::Revoked->value,
            'revocation_reason' => 'Issued to the wrong attendee.',
        ]);

        $this->getJson("/verify/{$certificate->credential_id}/checks")
            ->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('checks.0.passed', true)
            ->assertJsonPath('checks.3.passed', false)
            ->assertJsonPath('checks.3.detail', 'Issued to the wrong attendee.');
    }

    public function test_the_download_view_is_print_sized(): void
    {
        $certificate = $this->certificate();

        $this->get("/verify/{$certificate->credential_id}/download")
            ->assertOk()
            ->assertSee('size: A4 landscape', escape: false)
            ->assertSee('Raizel D. Galisanao');
    }
}
