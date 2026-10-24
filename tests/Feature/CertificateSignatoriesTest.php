<?php

namespace Tests\Feature;

use App\Filament\Pages\CertificateGenerator;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CertificateSignatoriesTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@abbadev.com'],
            ['name' => 'Admin', 'password' => 'password'],
        );
    }

    protected function certificate(array $overrides = []): Certificate
    {
        return Certificate::create([
            'credential_id' => 'aa11bb22cc33dd44ee55ff6677889900',
            'recipient_name' => 'Raizel D. Galisanao',
            'certificate_title' => 'Certificate of Participation',
            'signatory_one_name' => 'Rommel Galisanao',
            'signatory_one_role' => 'Founder / Resource Speaker',
            ...$overrides,
        ]);
    }

    public function test_the_signatories_layout_prints_founder_and_guest(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put(Certificate::SIGNATURE_DIRECTORY.'/guest.png', 'not-really-a-png');

        $certificate = $this->certificate([
            'layout' => 'signatories',
            'guest_signatories' => [[
                'name' => 'Dr. Maria Santos',
                'role' => 'Guest Speaker',
                'signature_path' => Certificate::SIGNATURE_DIRECTORY.'/guest.png',
                'signature_scale' => 110,
                'signature_offset_x' => -12,
                'signature_offset_y' => 6,
            ]],
        ]);

        $this->get("/verify/{$certificate->credential_id}")
            ->assertOk()
            ->assertSee('data-signatories="2"', escape: false)
            ->assertSee('Rommel Galisanao')
            ->assertSee('Dr. Maria Santos')
            ->assertSee(route('certificates.signature', 'guest.png'), escape: false)
            ->assertSee('--ecert-sig-scale: 110%', escape: false)
            ->assertSee('--ecert-sig-x: -12%', escape: false)
            ->assertSee('--ecert-sig-y: 6%', escape: false);
    }

    public function test_no_more_than_three_signatures_are_printed(): void
    {
        $certificate = $this->certificate([
            'layout' => 'signatories',
            'guest_signatories' => [
                ['name' => 'Guest One', 'role' => 'Speaker'],
                ['name' => 'Guest Two', 'role' => 'Speaker'],
                ['name' => 'Guest Three', 'role' => 'Speaker'],
            ],
        ]);

        $this->get("/verify/{$certificate->credential_id}")
            ->assertOk()
            ->assertSee('data-signatories="3"', escape: false)
            ->assertSee('Guest Two')
            ->assertDontSee('Guest Three');
    }

    public function test_the_standard_layout_ignores_guests(): void
    {
        $certificate = $this->certificate([
            'layout' => 'standard',
            'guest_signatories' => [['name' => 'Dr. Maria Santos', 'role' => 'Guest Speaker']],
        ]);

        $this->get("/verify/{$certificate->credential_id}")
            ->assertOk()
            ->assertDontSee('Dr. Maria Santos');
    }

    public function test_certificates_issued_before_the_layout_existed_render_unchanged(): void
    {
        $certificate = $this->certificate();

        $this->get("/verify/{$certificate->credential_id}")
            ->assertOk()
            ->assertSee('Rommel Galisanao')
            ->assertDontSee('class="ecert-signatures ecert-signatures--multi', escape: false);
    }

    public function test_the_generator_issues_a_guest_signatory(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CertificateGenerator::class)
            ->set('data.recipient_name', 'Raizel D. Galisanao')
            ->set('data.layout', 'signatories')
            ->set('data.guest_signatories', [['name' => 'Dr. Maria Santos', 'role' => 'Guest Speaker']])
            ->call('issue');

        $certificate = Certificate::sole();

        $this->assertSame('signatories', $certificate->layout);
        $this->assertSame('Dr. Maria Santos', $certificate->guest_signatories[0]['name']);
    }
}
