<?php

namespace App\Models;

use App\Enums\CertificateStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Certificate extends Model
{
    protected $fillable = [
        'credential_id',
        'recipient_name',
        'certificate_title',
        'layout',
        'presented_label',
        'activity_label',
        'activity_title',
        'conducted_by',
        'body_text',
        'signatory_one_name',
        'signatory_one_role',
        'signature_path',
        'signature_scale',
        'signature_offset_x',
        'signature_offset_y',
        'signatory_two_name',
        'signatory_two_role',
        'guest_signatories',
        'issued_on',
        'duration',
        'footer_note',
        'organization_name',
        'tagline',
        'accent',
        'seal_label',
        'show_logo',
        'show_seal',
        'show_qr',
        'status',
        'revocation_reason',
        'registration_id',
        'issued_by',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'signature_scale' => 'integer',
        'signature_offset_x' => 'integer',
        'signature_offset_y' => 'integer',
        'guest_signatories' => 'array',
        'show_logo' => 'boolean',
        'show_seal' => 'boolean',
        'show_qr' => 'boolean',
        'status' => CertificateStatus::class,
    ];

    /**
     * Where signature uploads live on the private disk.
     */
    public const SIGNATURE_DIRECTORY = 'certificate-signatures';

    /**
     * The fields the certificate template reads. Keeping this list in one place
     * means the admin preview and the public verification page render from the
     * same shape, whether the source is an unsaved form or a stored record.
     *
     * @var list<string>
     */
    public const TEMPLATE_FIELDS = [
        'credential_id',
        'recipient_name',
        'certificate_title',
        'layout',
        'presented_label',
        'activity_label',
        'activity_title',
        'conducted_by',
        'body_text',
        'signatory_one_name',
        'signatory_one_role',
        'signature_path',
        'signature_scale',
        'signature_offset_x',
        'signature_offset_y',
        'signatory_two_name',
        'signatory_two_role',
        'guest_signatories',
        'issued_on',
        'duration',
        'footer_note',
        'organization_name',
        'tagline',
        'accent',
        'seal_label',
        'show_logo',
        'show_seal',
        'show_qr',
    ];

    /**
     * The most signatures one certificate carries: the founder plus guests.
     * Past three the signature row stops fitting between the artwork's swoops.
     */
    public const MAX_GUEST_SIGNATORIES = 2;

    /**
     * Public URL for a stored or just-uploaded signature, whatever shape the
     * form state holds it in: a stored path, a Livewire temporary upload, or
     * the one-item array a file field wraps either in.
     */
    public static function signatureUrlFor(mixed $state): ?string
    {
        $file = is_array($state) ? Arr::first($state) : $state;

        if ($file instanceof TemporaryUploadedFile) {
            return $file->temporaryUrl();
        }

        return filled($file) ? route('certificates.signature', basename($file)) : null;
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isActive(): bool
    {
        return $this->status === CertificateStatus::Active;
    }

    public function verificationUrl(): string
    {
        return route('certificates.verify', $this->credential_id);
    }

    /**
     * Public URL for the signatory's e-signature, or null when none was
     * uploaded. Signatures live on the private disk and are streamed by name,
     * since they are printed on a public certificate anyway.
     */
    public function signatureUrl(): ?string
    {
        return static::signatureUrlFor($this->signature_path);
    }

    public function downloadUrl(): string
    {
        return route('certificates.download', $this->credential_id);
    }

    /**
     * The template's view data, with the date flattened to the Y-m-d string the
     * generator form also produces.
     *
     * @return array<string, mixed>
     */
    public function toTemplateArray(): array
    {
        $data = collect(static::TEMPLATE_FIELDS)
            ->mapWithKeys(fn (string $field): array => [$field => $this->{$field}])
            ->all();

        $data['issued_on'] = $this->issued_on?->toDateString();

        return $data;
    }
}
