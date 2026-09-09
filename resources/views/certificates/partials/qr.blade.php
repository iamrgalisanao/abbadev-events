{{-- Verification QR. Scanning it opens the public verification page for this
     credential, where the holder can run the authenticity checks or download
     the certificate. --}}
<div class="ecert-qr">
    <span class="ecert-qr-panel">
        {{ app(\App\Services\QrCodeRenderer::class)->svg($url) }}
    </span>
    <p class="ecert-qr-caption">Scan to verify</p>
</div>
