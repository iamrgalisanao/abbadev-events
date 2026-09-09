<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\HtmlString;

class QrCodeRenderer
{
    /**
     * Render a URL as an inline SVG QR code.
     *
     * SVG rather than a raster image so the code stays sharp at any print size,
     * and inline rather than a data URI so it can be styled and does not need a
     * second request when the certificate is printed.
     */
    public function svg(string $data, int $size = 320, int $margin = 1): HtmlString
    {
        $writer = new Writer(
            new ImageRenderer(new RendererStyle($size, $margin), new SvgImageBackEnd),
        );

        $svg = $writer->writeString($data);

        // Drop the XML prolog; an inline SVG inside an HTML document must not
        // carry one. Then let CSS size the element instead of the attributes.
        $svg = preg_replace('/^<\?xml.*?\?>\s*/', '', $svg);

        return new HtmlString(
            preg_replace('/<svg /', '<svg class="ecert-qr-svg" ', $svg, 1),
        );
    }
}
