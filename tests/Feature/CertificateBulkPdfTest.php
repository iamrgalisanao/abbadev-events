<?php

namespace Tests\Feature;

use App\Enums\CertificateStatus;
use App\Filament\Resources\Certificates\Pages\ListCertificates;
use App\Models\Certificate;
use App\Models\User;
use App\Services\CertificatePdfRenderer;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class CertificateBulkPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function certificate(string $credential, string $name, array $overrides = []): Certificate
    {
        return Certificate::create([
            'credential_id' => $credential,
            'recipient_name' => $name,
            'certificate_title' => 'Certificate of Participation',
            'signatory_one_name' => 'Rommel Galisanao',
            'signatory_one_role' => 'Founder / Resource Speaker',
            ...$overrides,
        ]);
    }

    protected function fakeRenderer(): void
    {
        $this->app->instance(CertificatePdfRenderer::class, new class extends CertificatePdfRenderer
        {
            public function pdf(Certificate $certificate): string
            {
                return '%PDF-fake '.$certificate->recipient_name;
            }
        });
    }

    public function test_each_certificate_is_a_separate_pdf_named_after_the_participant(): void
    {
        $this->fakeRenderer();

        $path = app(CertificatePdfRenderer::class)->zip(collect([
            $this->certificate('aa11bb22cc33dd44ee55ff6677889900', 'Raizel D. Galisanao'),
            $this->certificate('bb11bb22cc33dd44ee55ff6677889900', 'Juan dela Cruz'),
        ]));

        $zip = new ZipArchive;
        $zip->open($path);
        $names = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i))->sort()->values()->all();
        $zip->close();
        @unlink($path);

        $this->assertSame([
            'Juan dela Cruz - bb11bb22.pdf',
            'Raizel D. Galisanao - aa11bb22.pdf',
        ], $names);
    }

    public function test_the_same_name_does_not_overwrite_itself(): void
    {
        $a = $this->certificate('aa11bb22cc33dd44ee55ff6677889900', 'Maria Santos');
        $b = $this->certificate('bb11bb22cc33dd44ee55ff6677889900', 'Maria Santos');

        $this->assertNotSame(CertificatePdfRenderer::fileName($a), CertificatePdfRenderer::fileName($b));
    }

    public function test_unsafe_characters_are_stripped_from_the_file_name(): void
    {
        $certificate = $this->certificate('aa11bb22cc33dd44ee55ff6677889900', 'A/B\\C: "D"?');

        $this->assertDoesNotMatchRegularExpression('/[\\\\\/:*?"<>|]/', CertificatePdfRenderer::fileName($certificate));
    }

    public function test_the_bulk_action_downloads_a_zip_and_skips_revoked_certificates(): void
    {
        $this->fakeRenderer();

        $admin = User::firstOrCreate(['email' => 'admin@abbadev.com'], ['name' => 'Admin', 'password' => 'password']);
        $ok = $this->certificate('aa11bb22cc33dd44ee55ff6677889900', 'Raizel D. Galisanao');
        $revoked = $this->certificate('bb11bb22cc33dd44ee55ff6677889900', 'Juan dela Cruz', ['status' => CertificateStatus::Revoked]);

        Livewire::actingAs($admin)
            ->test(ListCertificates::class)
            ->selectTableRecords([$ok->getKey(), $revoked->getKey()])
            ->callAction(TestAction::make('downloadPdfs')->table()->bulk())
            ->assertFileDownloaded();
    }
}
