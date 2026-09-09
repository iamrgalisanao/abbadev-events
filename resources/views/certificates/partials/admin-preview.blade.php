@php
    $certificate = $getRecord();
@endphp

@include('certificates.partials.styles')

<div style="max-width: 940px;">
    @include('certificates.partials.certificate', [
        'c' => $certificate->toTemplateArray(),
        'verificationUrl' => $certificate->verificationUrl(),
    ])
</div>
