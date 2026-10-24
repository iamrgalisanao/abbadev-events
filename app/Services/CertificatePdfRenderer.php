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
     * Bulk downloads are rendered while the request waits (about 4 seconds per
     * certificate), so the batch size is capped to keep it inside the web
     * server's request timeout, 60 seconds by default.
     */
    public static function maxBatch(): int
    {
        return max(1, (int) config('services.browsershot.max_batch', 15));
    }

    /** A4 landscape at 96 dpi, and the resolution multiplier for the image. */
    protected const SHEET_WIDTH_PX = 1123;

    protected const SHEET_HEIGHT_PX = 794;

    protected const SCALE = 3;

    /**
     * The certificate as a one-page A4 landscape PDF.
     *
     * Chrome's own PDF output turns the design's gradient text, transparent
     * gradients, SVG artwork and blend modes into constructs that PDF viewers
     * draw differently, so a "vector" PDF came out looking different in each.
     * Instead the page is rendered exactly as the browser shows it, at 3x, and
     * that image is placed full-bleed on the sheet: every viewer shows the same
     * certificate. The trade-off is that the text is not selectable.
     */
    public function pdf(Certificate $certificate): string
    {
        $html = view('certificates.pdf', ['certificate' => $certificate])->render();

        $image = $this->configure(Browsershot::html($html))
            ->windowSize(self::SHEET_WIDTH_PX, self::SHEET_HEIGHT_PX)
            ->deviceScaleFactor(self::SCALE)
            ->setScreenshotType('jpeg', 92)
            ->waitUntilNetworkIdle()
            ->setDelay(300)
            ->screenshot();

        $sheet = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
            .'@page{size:297mm 210mm;margin:0}'
            .'html,body{margin:0;padding:0;width:297mm;height:210mm;overflow:hidden;background:#0a1a33}'
            .'img{display:block;width:297mm;height:210mm}'
            .'</style></head><body><img src="data:image/jpeg;base64,'.base64_encode($image).'"></body></html>';

        return $this->configure(Browsershot::html($sheet))
            ->paperSize(297, 210, 'mm')
            ->margins(0, 0, 0, 0)
            ->showBackground()
            ->pdf();
    }

    /**
     * Binaries, sandbox and timeout settings shared by both Chrome runs.
     */
    protected function configure(Browsershot $shot): Browsershot
    {
        $config = config('services.browsershot');

        $shot->timeout($config['timeout']);

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

        // The crash reporter wants a writable home directory, which a web
        // server user such as www-data does not have, and Chrome dies on start
        // ("chrome_crashpad_handler: --database is required").
        $shot->addChromiumArguments(['disable-crash-reporter', 'disable-breakpad']);

        return $shot;
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
