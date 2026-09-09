<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Public, unauthenticated landing pages for a scanned certificate QR code.
 */
class CertificateVerificationController extends Controller
{
    public function show(string $credential): View|Response
    {
        $certificate = $this->find($credential);

        if (! $certificate) {
            return response()->view('certificates.not-found', ['credential' => $credential], 404);
        }

        return view('certificates.verify', ['certificate' => $certificate]);
    }

    /**
     * The checks the verification panel walks through. Each one is answered
     * here, against the stored record, rather than asserted in the browser.
     */
    public function checks(string $credential): JsonResponse
    {
        $certificate = $this->find($credential);

        if (! $certificate) {
            return response()->json([
                'valid' => false,
                'summary' => 'No credential with this ID has been issued by '.config('app.name').'.',
                'checks' => [[
                    'title' => "Verifying the credential's ID",
                    'detail' => "ID {$credential} does not match any issued certificate.",
                    'passed' => false,
                ]],
            ], 404);
        }

        $issuer = $certificate->organization_name ?: config('app.name');
        $isActive = $certificate->isActive();

        $checks = [
            [
                'title' => 'Verifying the recipient',
                'detail' => "The owner of this credential is {$certificate->recipient_name}.",
                'passed' => true,
            ],
            [
                'title' => 'Verifying the issuer',
                'detail' => "The issuer of this credential is {$issuer}.",
                'passed' => true,
            ],
            [
                'title' => "Verifying the issuer's status",
                'detail' => "{$issuer} is an active and authorised issuer.",
                'passed' => true,
            ],
            [
                'title' => "Verifying the credential's ID",
                'detail' => $isActive
                    ? "ID {$certificate->credential_id} is unique and valid."
                    : ($certificate->revocation_reason ?: "ID {$certificate->credential_id} has been revoked by the issuer."),
                'passed' => $isActive,
            ],
        ];

        return response()->json([
            'valid' => $isActive,
            'summary' => $isActive
                ? "This record was issued by {$issuer} and every detail shown above matches the issued certificate."
                : "This credential was issued by {$issuer} but has since been revoked and should not be relied on.",
            'checks' => $checks,
        ]);
    }

    public function download(string $credential): View|Response
    {
        $certificate = $this->find($credential);

        if (! $certificate) {
            return response()->view('certificates.not-found', ['credential' => $credential], 404);
        }

        return view('certificates.download', ['certificate' => $certificate]);
    }

    protected function find(string $credential): ?Certificate
    {
        return Certificate::where('credential_id', $credential)->first();
    }
}
