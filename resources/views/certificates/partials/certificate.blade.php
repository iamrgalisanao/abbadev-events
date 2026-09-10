@php
    use Illuminate\Support\Carbon;

    $value = fn (string $key, ?string $fallback = null) => filled($c[$key] ?? null) ? trim((string) $c[$key]) : $fallback;

    $recipient = $value('recipient_name', 'Recipient Name');
    $activityTitle = $value('activity_title');
    $issuedOn = filled($c['issued_on'] ?? null) ? Carbon::parse($c['issued_on']) : null;

    // The three headline lines are the only copy that can break the layout, so
    // each steps down through fixed sizes to stay inside the 66cqw safe area
    // between the artwork's swoops instead of wrapping or colliding with them.
    $nameLength = mb_strlen($recipient);
    $nameSize = match (true) {
        $nameLength <= 14 => '7cqw',
        $nameLength <= 20 => '5.9cqw',
        $nameLength <= 28 => '4.6cqw',
        $nameLength <= 38 => '3.5cqw',
        $nameLength <= 50 => '2.7cqw',
        default => '2.2cqw',
    };

    $titleLength = mb_strlen((string) $value('certificate_title'));
    $titleSize = match (true) {
        $titleLength <= 22 => '3.3cqw',
        $titleLength <= 30 => '2.9cqw',
        $titleLength <= 40 => '2.3cqw',
        default => '1.9cqw',
    };

    $activityLength = mb_strlen((string) $activityTitle);
    $activitySize = match (true) {
        $activityLength <= 30 => '3.1cqw',
        $activityLength <= 45 => '2.6cqw',
        $activityLength <= 60 => '2cqw',
        $activityLength <= 80 => '1.5cqw',
        default => '1.25cqw',
    };

    $meta = collect([
        ['label' => 'Credential ID', 'value' => $value('credential_id')],
        ['label' => 'Session date', 'value' => $issuedOn?->format('F j, Y')],
        ['label' => 'Duration', 'value' => $value('duration')],
    ])->filter(fn (array $item) => filled($item['value']))->values();

    $showLogo = (bool) ($c['show_logo'] ?? true);
    $sealYear = $issuedOn?->format('Y') ?? now()->format('Y');

    // One badge sits in the signature row: the QR when there is a credential
    // to scan, otherwise the seal.
    $verificationUrl = $verificationUrl ?? null;
    $showQr = (bool) ($c['show_qr'] ?? true) && filled($verificationUrl);
    $showSeal = ! $showQr && (bool) ($c['show_seal'] ?? true);

@endphp

<div
    class="ecert-frame"
    data-accent="{{ $c['accent'] ?? 'blue' }}"
    style="--ecert-name-size: {{ $nameSize }}; --ecert-title-size: {{ $titleSize }}; --ecert-activity-size: {{ $activitySize }};"
>
    <img class="ecert-art" src="{{ asset('images/abbadev_certificate_background.svg') }}" alt="" />

    <div class="ecert-body">
        <header class="ecert-head">
            @if ($showLogo || filled($value('organization_name')) || filled($value('tagline')))
                <div class="ecert-brand">
                    @if ($showLogo)
                        <img class="ecert-logo" src="{{ asset('images/abbadev-logo.png') }}" alt="" />
                    @endif
                    @if (filled($value('organization_name')) || filled($value('tagline')))
                        <span class="ecert-lockup">
                            @if (filled($value('organization_name')))
                                <span class="ecert-wordmark">{{ $value('organization_name') }}</span>
                            @endif
                            @if (filled($value('tagline')))
                                <span class="ecert-tagline">{{ $value('tagline') }}</span>
                            @endif
                        </span>
                    @endif
                </div>
            @endif

            @if (filled($value('certificate_title')))
                <h1 class="ecert-title">{{ $value('certificate_title') }}</h1>
            @endif
        </header>

        <div class="ecert-main">
            @if (filled($value('presented_label')))
                <p class="ecert-lead">{{ $value('presented_label') }}</p>
            @endif

            <p class="ecert-name">{{ $recipient }}</p>

            <span class="ecert-rule" aria-hidden="true"></span>

            @if (filled($value('activity_label')))
                <p class="ecert-lead">{{ $value('activity_label') }}</p>
            @endif

            @if (filled($activityTitle))
                <p class="ecert-activity">{{ $activityTitle }}</p>
            @endif

            @if (filled($value('conducted_by')))
                <p class="ecert-lead">{{ $value('conducted_by') }}</p>
            @endif

            @if (filled($value('body_text')))
                <p class="ecert-copy">{{ $value('body_text') }}</p>
            @endif
        </div>

        <footer class="ecert-foot">
            {{-- Centred row: the badge and whichever signatories are filled
                 in sit together, so one signatory is not left off-axis. --}}
            <div class="ecert-signatures">
                @if (filled($value('signatory_one_name')) || filled($value('signatory_one_role')))
                    <div class="ecert-signature">
                        <span class="ecert-signature-rule" aria-hidden="true"></span>
                        <p class="ecert-signature-name">{{ $value('signatory_one_name', ' ') }}</p>
                        <p class="ecert-signature-role">{{ $value('signatory_one_role') }}</p>
                    </div>
                @endif

                @if ($showQr)
                    @include('certificates.partials.qr', ['url' => $verificationUrl])
                @elseif ($showSeal)
                    @include('certificates.partials.seal', [
                        'label' => $value('seal_label', 'CERTIFIED'),
                        'year' => $sealYear,
                    ])
                @endif

                {{-- Certificates issued back when the template carried two
                     signatories still render both, exactly as they were. --}}
                @if (filled($value('signatory_two_name')) || filled($value('signatory_two_role')))
                    <div class="ecert-signature">
                        <span class="ecert-signature-rule" aria-hidden="true"></span>
                        <p class="ecert-signature-name">{{ $value('signatory_two_name', ' ') }}</p>
                        <p class="ecert-signature-role">{{ $value('signatory_two_role') }}</p>
                    </div>
                @endif
            </div>

            @if ($meta->isNotEmpty())
                <dl class="ecert-meta">
                    @foreach ($meta as $item)
                        <div class="ecert-meta-item">
                            <dt>{{ $item['label'] }}</dt>
                            <dd>{{ $item['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif

            @if (filled($value('footer_note')))
                <p class="ecert-note">{{ $value('footer_note') }}</p>
            @endif
        </footer>
    </div>
</div>
