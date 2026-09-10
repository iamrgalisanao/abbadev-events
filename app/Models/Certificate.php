<?php

namespace App\Models;

use App\Enums\CertificateStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'credential_id',
        'recipient_name',
        'certificate_title',
        'presented_label',
        'activity_label',
        'activity_title',
        'conducted_by',
        'body_text',
        'signatory_one_name',
        'signatory_one_role',
        'signatory_two_name',
        'signatory_two_role',
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
        'show_logo' => 'boolean',
        'show_seal' => 'boolean',
        'show_qr' => 'boolean',
        'status' => CertificateStatus::class,
    ];

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
        'presented_label',
        'activity_label',
        'activity_title',
        'conducted_by',
        'body_text',
        'signatory_one_name',
        'signatory_one_role',
        'signatory_two_name',
        'signatory_two_role',
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
