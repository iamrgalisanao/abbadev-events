<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>{{ $certificate->recipient_name }} — certificate</title>

    @include('certificates.partials.styles')

    {{-- Screenshotted by headless Chrome at 3x (see CertificatePdfRenderer), so
         the sheet is a fixed A4-landscape pixel size, edge to edge. --}}
    @verbatim
        <style>
            @page {
                size: A4 landscape;
                margin: 0;
            }

            html,
            body {
                width: 1123px;
                height: 794px;
                margin: 0;
                padding: 0;
                overflow: hidden;
                background: #fff;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .ecert-frame {
                width: 1123px;
                height: 794px;
                aspect-ratio: auto;
                border-radius: 0;
                box-shadow: none;
            }
        </style>
    @endverbatim
</head>
<body>
    @php
        $inline = fn (?string $path): ?string => \App\Support\InlineAsset::dataUri($path);
    @endphp

    @include('certificates.partials.certificate', [
        'c' => $certificate->toTemplateArray(),
        'verificationUrl' => $certificate->verificationUrl(),
        'signatureUrl' => \App\Support\InlineAsset::signature($certificate->signature_path),
        'artUrl' => $inline(public_path('images/abbadev_certificate_background.svg'))
            ?? asset('images/abbadev_certificate_background.svg'),
        'logoUrl' => $inline(public_path('images/abbadev-logo.png'))
            ?? asset('images/abbadev-logo.png'),
        'inlineSignature' => fn ($stored) => \App\Support\InlineAsset::signature($stored),
    ])
</body>
</html>
