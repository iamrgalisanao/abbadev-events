<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>{{ $certificate->recipient_name }} — certificate</title>

    @include('certificates.partials.styles')

    {{-- Rendered by headless Chrome straight to one A4 landscape page, so the
         print rules of the download view apply unconditionally here. --}}
    @verbatim
        <style>
            @page {
                size: A4 landscape;
                margin: 0;
            }

            html,
            body {
                width: 297mm;
                height: 210mm;
                margin: 0;
                padding: 0;
                overflow: hidden;
                background: #fff;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .ecert-frame {
                width: 297mm;
                height: 210mm;
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
