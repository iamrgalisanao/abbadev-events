<?php

namespace App\Support;

class InlineAsset
{
    /**
     * Read a file into a data URI.
     *
     * The print/download view embeds its images this way so the certificate a
     * browser rasterises carries its own pixels. A linked <img> has to still be
     * fetchable and decoded at the moment the print job runs; a data URI is
     * already part of the document, which also means a saved PDF does not
     * depend on the asset host at all.
     */
    public static function dataUri(?string $absolutePath): ?string
    {
        if (blank($absolutePath) || ! is_file($absolutePath)) {
            return null;
        }

        $contents = @file_get_contents($absolutePath);

        if ($contents === false) {
            return null;
        }

        return 'data:'.static::mimeType($absolutePath).';base64,'.base64_encode($contents);
    }

    protected static function mimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };
    }
}
