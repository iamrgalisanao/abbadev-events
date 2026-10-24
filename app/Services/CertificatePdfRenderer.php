<?php

namespace App\Services;

use App\Models\Certificate;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Browsershot\Browsershot;
use ZipArchive;

/**
 * Renders issued certificates to PDF with headless Chrome, using the same
 * template (and so the same A4 landscape layout) as the on-screen print view.
 */
class CertificatePdfRenderer
{
    /**
     * Bulk downloads are rendered while the request waits, so the batch size is
     * capped to keep it inside the web server's timeout.
     */
    public const MAX_BATCH = 40;

    public function pdf(Certificate $certificate): string
    {
        $html = view('certificates.pdf', ['certificate' => $certificate])->render();

        $config = config('services.browsershot');

        $shot = Browsershot::html($html)
            ->setTimeout($config['timeout'])
            ->paperSize(297, 210, 'mm')
            ->margins(0, 0, 0, 0)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->setDelay(300);

        if ($config['node_binary']) {
            $shot->setNodeBinary($config['node_binary']);
        }

        if ($config['npm_binary']) {
            $shot->setNpmBinary($config['npm_binary']);
        }

        if ($config['chrome_path']) {
            $shot->setChromePath($config['chrome_path']);
        }

        if ($config['no_sandbox']) {
            $shot->noSandbox();
        }

        return $shot->pdf();
    }

    /**
     * One PDF per certificate, zipped. Returns the path of the temporary
     * archive; the caller deletes it once it has been sent.
     *
     * @param  Collection<int, Certificate>  $certificates
     */
    public function zip(Collection $certificates): string
    {
        $path = tempnam(sys_get_temp_dir(), 'certs').'.zip';
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the certificate archive.');
        }

        foreach ($certificates as $certificate) {
            $zip->addFromString(static::fileName($certificate), $this->pdf($certificate));
        }

        $zip->close();

        return $path;
    }

    /**
     * "Raizel D. Galisanao - 54a229f9.pdf". The credential suffix keeps two
     * attendees with the same name from overwriting each other in the archive.
     */
    public static function fileName(Certificate $certificate): string
    {
        $name = trim((string) preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', ' ', (string) $certificate->recipient_name));
        $name = Str::limit(preg_replace('/\s+/u', ' ', $name) ?: 'Certificate', 80, '');

        return trim($name).' - '.Str::substr((string) $certificate->credential_id, 0, 8).'.pdf';
    }
}
