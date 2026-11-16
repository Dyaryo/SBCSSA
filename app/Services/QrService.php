<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Renders a scannable QR code as inline SVG: no external service, no image
 * files on disk and no CSP relaxation (inline SVG is not a fetch).
 */
class QrService
{
    public function svg(string $text, int $size = 160, int $margin = 1): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size, $margin), new SvgImageBackEnd());

        $svg = (new Writer($renderer))->writeString($text);

        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg) ?? $svg;
    }
}
