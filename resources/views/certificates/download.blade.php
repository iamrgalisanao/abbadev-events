<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex" />
    <title>{{ $certificate->recipient_name }} — certificate</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any" />

    @include('certificates.partials.styles')

    @verbatim
        <style>
            body {
                margin: 0;
                background: oklch(15.5% 0.042 252);
                font: 15px/1.5 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            }

            .dl-bar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: center;
                gap: 12px;
                padding: 16px;
                color: oklch(86% 0.018 248);
            }

            .dl-bar button,
            .dl-bar a {
                padding: 11px 18px;
                border: 1px solid oklch(100% 0 0 / 0.28);
                border-radius: 9px;
                background: transparent;
                color: oklch(98.5% 0.004 250);
                font: inherit;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
            }

            .dl-bar button {
                border-color: transparent;
                background: oklch(55% 0.19 258);
            }

            .dl-hint {
                width: 100%;
                text-align: center;
                font-size: 13px;
                color: oklch(64% 0.03 248);
            }

            .dl-stage {
                max-width: 1180px;
                margin: 0 auto;
                padding: 0 16px 40px;
            }

            @page {
                size: A4 landscape;
                margin: 0;
            }

            @media print {
                body {
                    background: #fff;
                }

                .dl-bar {
                    display: none;
                }

                .dl-stage {
                    max-width: none;
                    margin: 0;
                    padding: 0;
                }

                .ecert-frame {
                    width: 297mm;
                    height: 210mm;
                    aspect-ratio: auto;
                    border-radius: 0;
                    box-shadow: none;
                }
            }
        </style>
    @endverbatim
</head>
<body>
    <div class="dl-bar">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ $certificate->verificationUrl() }}">Back to verification</a>
        <p class="dl-hint">Choose &ldquo;Save as PDF&rdquo; as the destination, A4 landscape, margins none.</p>
    </div>

    <div class="dl-stage">
        @php
            // Embedded rather than linked: this page exists to be rasterised
            // into a PDF, and a linked image has to still be fetchable and
            // decoded at the moment the print job runs. The saved file also
            // then carries its own pixels, so it survives being emailed on.
            $inline = fn (?string $path): ?string => \App\Support\InlineAsset::dataUri($path);
            $signaturePath = filled($certificate->signature_path)
                ? \Illuminate\Support\Facades\Storage::disk('local')->path($certificate->signature_path)
                : null;
        @endphp

        @include('certificates.partials.certificate', [
            'c' => $certificate->toTemplateArray(),
            'verificationUrl' => $certificate->verificationUrl(),
            'signatureUrl' => $inline($signaturePath) ?? $certificate->signatureUrl(),
            'artUrl' => $inline(public_path('images/abbadev_certificate_background.svg'))
                ?? asset('images/abbadev_certificate_background.svg'),
            'logoUrl' => $inline(public_path('images/abbadev-logo.png'))
                ?? asset('images/abbadev-logo.png'),
        ])
    </div>

    <script>
        // Give the fonts and the QR a moment to paint before the dialog opens,
        // otherwise the print preview can capture a fallback-font first frame.
        window.addEventListener('load', () => {
            const ready = document.fonts ? document.fonts.ready : Promise.resolve()
            ready.then(() => setTimeout(() => window.print(), 250))
        })
    </script>
</body>
</html>
