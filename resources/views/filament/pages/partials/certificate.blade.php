@php
    use Illuminate\Support\Carbon;

    $value = fn (string $key, ?string $fallback = null) => filled($c[$key] ?? null) ? trim((string) $c[$key]) : $fallback;

    $recipient = $value('recipient_name', 'Recipient Name');
    $activityTitle = $value('activity_title');
    $issuedOn = filled($c['issued_on'] ?? null) ? Carbon::parse($c['issued_on']) : null;

    // The three headline lines are the only copy that can break the layout, so
    // each steps down through fixed sizes to stay inside the 66cqw safe area
    // between the gold sweeps instead of wrapping or colliding with them.
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

    $showSeal = (bool) ($c['show_seal'] ?? true);
    $showLogo = (bool) ($c['show_logo'] ?? true);
    $sealYear = $issuedOn?->format('Y') ?? now()->format('Y');

    // Drawn once on the left, then echoed again mirrored onto the right edge.
    $ornament = <<<'SVG'
        <path d="M0,0 H54 C18,60 18,150 64,210 H0 Z" fill="url(#ecertPetal)" opacity="0.9" />
        <path d="M0,0 H54 C18,60 18,150 64,210 H0 Z" fill="url(#ecertGrain)" />
        <path d="M0,0 H28 C2,62 4,150 34,210 H0 Z" fill="var(--ecert-navy-1)" opacity="0.85" />
        <path d="M62,0 C26,60 26,150 72,210 L64,210 C18,150 18,60 54,0 Z" fill="url(#ecertSweep)" />
        <path d="M70,0 C34,60 34,150 80,210" fill="none" stroke="url(#ecertSweep)" stroke-width="0.6" opacity="0.45" />
        <path d="M76,0 C40,60 40,150 86,210" fill="none" stroke="url(#ecertSweep)" stroke-width="0.25" opacity="0.28" />
        SVG;
@endphp

<div
    class="ecert-frame"
    data-accent="{{ $c['accent'] ?? 'gold' }}"
    style="--ecert-name-size: {{ $nameSize }}; --ecert-title-size: {{ $titleSize }}; --ecert-activity-size: {{ $activitySize }};"
>
    <svg class="ecert-art" viewBox="0 0 297 210" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <defs>
            <linearGradient id="ecertSweep" x1="0" y1="0" x2="0.9" y2="1">
                <stop offset="0" stop-color="var(--ecert-accent-1)" />
                <stop offset="0.42" stop-color="var(--ecert-accent-3)" />
                <stop offset="0.7" stop-color="var(--ecert-accent-2)" />
                <stop offset="1" stop-color="var(--ecert-accent-1)" />
            </linearGradient>
            <linearGradient id="ecertPetal" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="var(--ecert-navy-2)" />
                <stop offset="1" stop-color="var(--ecert-navy-0)" />
            </linearGradient>
            <pattern id="ecertGrain" width="1.9" height="4" patternUnits="userSpaceOnUse">
                <rect width="0.3" height="4" fill="var(--ecert-paper)" opacity="0.13" />
            </pattern>
        </defs>

        <g>{!! $ornament !!}</g>
        <g transform="translate(297,0) scale(-1,1)">{!! $ornament !!}</g>

        <rect
            x="9.5" y="8" width="278" height="194" rx="2"
            fill="none" stroke="url(#ecertSweep)" stroke-width="0.35" opacity="0.55"
        />
    </svg>

    <div class="ecert-body">
        <header class="ecert-head">
            @if ($showLogo || filled($value('organization_name')))
                <div class="ecert-brand">
                    @if ($showLogo)
                        <img class="ecert-logo" src="{{ asset('images/abbadev-logo.png') }}" alt="" />
                    @endif
                    @if (filled($value('organization_name')))
                        <span class="ecert-wordmark">{{ $value('organization_name') }}</span>
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
            {{-- Always a three-cell row so the seal stays centred whether one,
                 two, or no signatories are filled in. --}}
            <div class="ecert-signatures">
                <div class="ecert-signature">
                    @if (filled($value('signatory_one_name')) || filled($value('signatory_one_role')))
                        <span class="ecert-signature-rule" aria-hidden="true"></span>
                        <p class="ecert-signature-name">{{ $value('signatory_one_name', ' ') }}</p>
                        <p class="ecert-signature-role">{{ $value('signatory_one_role') }}</p>
                    @endif
                </div>

                <div class="ecert-seal-cell">
                    @if ($showSeal)
                        @include('filament.pages.partials.certificate-seal', [
                            'label' => $value('seal_label', 'CERTIFIED'),
                            'year' => $sealYear,
                        ])
                    @endif
                </div>

                <div class="ecert-signature">
                    @if (filled($value('signatory_two_name')) || filled($value('signatory_two_role')))
                        <span class="ecert-signature-rule" aria-hidden="true"></span>
                        <p class="ecert-signature-name">{{ $value('signatory_two_name', ' ') }}</p>
                        <p class="ecert-signature-role">{{ $value('signatory_two_role') }}</p>
                    @endif
                </div>
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
