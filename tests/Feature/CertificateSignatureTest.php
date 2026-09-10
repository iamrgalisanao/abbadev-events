<?php

namespace Tests\Feature;

use App\Filament\Pages\CertificateGenerator;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CertificateSignatureTest extends TestCase
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

    public function test_an_uploaded_signature_is_stored_and_kept_on_the_certificate(): void
    {
        Storage::fake('local');

        Livewire::actingAs($this->admin())
            ->test(CertificateGenerator::class)
            ->set('data.recipient_name', 'Raizel D. Galisanao')
            ->set('data.signature_path', [UploadedFile::fake()->image('signature.png')])
            ->call('issue');

        $certificate = Certificate::sole();

        $this->assertStringStartsWith(Certificate::SIGNATURE_DIRECTORY.'/', $certificate->signature_path);
        Storage::disk('local')->assertExists($certificate->signature_path);
    }

    public function test_the_certificate_prints_the_signature_above_the_name(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put(Certificate::SIGNATURE_DIRECTORY.'/sig.png', 'not-really-a-png');

        $certificate = $this->certificate([
            'signature_path' => Certificate::SIGNATURE_DIRECTORY.'/sig.png',
        ]);

        $this->get("/verify/{$certificate->credential_id}")
            ->assertOk()
            ->assertSee('ecert-signature-mark', escape: false)
            ->assertSee(route('certificates.signature', 'sig.png'), escape: false);
    }

    public function test_a_certificate_without_a_signature_prints_none(): void
    {
        $certificate = $this->certificate();

        $this->get("/verify/{$certificate->credential_id}")
            ->assertOk()
            ->assertDontSee('<img class="ecert-signature-mark"', escape: false)
            ->assertSee('Rommel Galisanao');
    }

    public function test_the_signature_route_streams_the_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put(Certificate::SIGNATURE_DIRECTORY.'/sig.png', 'not-really-a-png');

        $response = $this->get(route('certificates.signature', 'sig.png'))->assertOk();

        // Symfony reorders the directives, so this checks for the one that
        // matters rather than the exact header string.
        $this->assertStringContainsString('max-age=31536000', $response->headers->get('Cache-Control'));
    }

    public function test_the_signature_route_will_not_serve_anything_outside_its_directory(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('secrets.txt', 'private');

        // The route pattern rejects separators outright, and the controller
        // takes only the basename, so neither shape escapes the directory.
        $this->get('/certificate-signature/..%2Fsecrets.txt')->assertNotFound();
        $this->get('/certificate-signature/secrets.txt')->assertNotFound();
    }

    public function test_a_batch_carries_the_signature_onto_every_certificate(): void
    {
        Storage::fake('local');

        $event = Event::create([
            'slug' => 'signed-session',
            'title' => 'Web Dev 101: Go Live with Vercel',
            'type' => 'Webinar',
            'mode' => 'Online',
            'event_code' => 'WEB',
            'expected_amount' => 0,
            'starts_at' => now()->subDays(3),
            'is_active' => true,
        ]);

        foreach (['Raizel D Galisanao', 'Carl Jethro Babiano'] as $index => $name) {
            Registration::create([
                'registration_number' => 'ABBA-WEB-2026-000'.$index,
                'name' => $name,
                'email' => "attendee{$index}@example.com",
                'event_id' => $event->id,
                'status' => 'confirmed',
            ]);
        }

        Livewire::actingAs($this->admin())
            ->test(CertificateGenerator::class)
            ->set('data.event_id', $event->id)
            ->set('data.certificate_title', 'Certificate of Participation')
            ->set('data.signature_path', [UploadedFile::fake()->image('signature.png')])
            ->call('bulkIssue', false);

        $paths = Certificate::pluck('signature_path');

        $this->assertCount(2, $paths);
        $this->assertCount(1, $paths->unique(), 'Every certificate in the batch should point at the one uploaded file.');
        Storage::disk('local')->assertExists($paths->first());
    }
}
