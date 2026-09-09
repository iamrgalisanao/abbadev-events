<?php

namespace Tests\Feature;

use App\Enums\CertificateStatus;
use App\Filament\Certificates\CertificateActions;
use App\Filament\Pages\CertificateGenerator;
use App\Filament\Resources\Certificates\Pages\ListCertificates;
use App\Filament\Resources\Certificates\Pages\ViewCertificate;
use App\Models\Certificate;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CertificateAdminTest extends TestCase
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

    protected function certificate(array $overrides = []): Certificate
    {
        return Certificate::create([
            'credential_id' => 'aa11bb22cc33dd44ee55ff6677889900',
            'recipient_name' => 'Raizel D. Galisanao',
            'certificate_title' => 'Certificate of Participation',
            'activity_title' => 'Web Dev 101: Go Live with Vercel',
            'organization_name' => 'ABBADEV',
            'issued_on' => '2026-08-28',
            ...$overrides,
        ]);
    }

    public function test_the_list_shows_issued_certificates(): void
    {
        $certificate = $this->certificate();

        Livewire::actingAs($this->admin())
            ->test(ListCertificates::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$certificate]);
    }

    public function test_revoking_records_the_reason_and_shows_it_publicly(): void
    {
        $certificate = $this->certificate();

        Livewire::actingAs($this->admin())
            ->test(ViewCertificate::class, ['record' => $certificate->getKey()])
            ->callAction(
                TestAction::make(CertificateActions::revoke()->getName())->schemaComponent(null),
                ['revocation_reason' => 'Issued to the wrong attendee.'],
            );

        $certificate->refresh();

        $this->assertSame(CertificateStatus::Revoked, $certificate->status);
        $this->assertSame('Issued to the wrong attendee.', $certificate->revocation_reason);

        $this->getJson("/verify/{$certificate->credential_id}/checks")
            ->assertJsonPath('valid', false)
            ->assertJsonPath('checks.3.detail', 'Issued to the wrong attendee.');
    }

    public function test_reinstating_clears_the_revocation(): void
    {
        $certificate = $this->certificate([
            'status' => CertificateStatus::Revoked->value,
            'revocation_reason' => 'Issued to the wrong attendee.',
        ]);

        Livewire::actingAs($this->admin())
            ->test(ViewCertificate::class, ['record' => $certificate->getKey()])
            ->callAction(TestAction::make(CertificateActions::reinstate()->getName())->schemaComponent(null));

        $certificate->refresh();

        $this->assertSame(CertificateStatus::Active, $certificate->status);
        $this->assertNull($certificate->revocation_reason);

        $this->getJson("/verify/{$certificate->credential_id}/checks")
            ->assertJsonPath('valid', true);
    }

    public function test_the_generator_loads_a_certificate_passed_in_the_url(): void
    {
        $certificate = $this->certificate();

        Livewire::actingAs($this->admin())
            ->withQueryParams(['certificate' => $certificate->getKey()])
            ->test(CertificateGenerator::class)
            ->assertSchemaStateSet([
                'certificate_id' => $certificate->getKey(),
                'credential_id' => $certificate->credential_id,
                'recipient_name' => 'Raizel D. Galisanao',
            ], schema: 'form');
    }

    public function test_issuing_stores_the_certificate_against_its_credential_id(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(CertificateGenerator::class)
            ->fillForm([
                'recipient_name' => 'Marco A. Reyes',
                'credential_id' => 'ff00ff00ff00ff00ff00ff00ff00ff00',
                'activity_title' => 'Idea to Intelligent System',
            ])
            ->call('issue');

        $certificate = Certificate::firstWhere('credential_id', 'ff00ff00ff00ff00ff00ff00ff00ff00');

        $this->assertNotNull($certificate);
        $this->assertSame('Marco A. Reyes', $certificate->recipient_name);
        $this->assertSame('Idea to Intelligent System', $certificate->activity_title);
        $this->assertSame($admin->id, $certificate->issued_by);
    }

    public function test_re_issuing_the_same_credential_amends_the_existing_record(): void
    {
        $admin = $this->admin();
        $certificate = $this->certificate();

        Livewire::actingAs($admin)
            ->withQueryParams(['certificate' => $certificate->getKey()])
            ->test(CertificateGenerator::class)
            ->fillForm(['recipient_name' => 'Raizel Dela Cruz Galisanao'])
            ->call('issue');

        $this->assertSame(1, Certificate::count());
        $this->assertSame('Raizel Dela Cruz Galisanao', $certificate->fresh()->recipient_name);
    }
}
