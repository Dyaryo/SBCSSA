<?php
/**
 * Draws the rooster-head tab icon without any image extension: the pixels are
 * computed with plain arithmetic and written as a multi-size .ico (32-bit
 * bitmap entries, which every browser accepts).
 *
 * Run:  php scripts/make-favicon.php
 */
$sizes = [16, 32, 48];

// Shape helpers on a 0..1 canvas, so every size is drawn at its own resolution
// instead of being scaled down from one bitmap.
function inCircle(float $x, float $y, float $cx, float $cy, float $r): bool
{
    return (($x - $cx) ** 2 + ($y - $cy) ** 2) <= $r ** 2;
}

function inTriangle(float $x, float $y, array $a, array $b, array $c): bool
{
    $d1 = ($x - $b[0]) * ($a[1] - $b[1]) - ($a[0] - $b[0]) * ($y - $b[1]);
    $d2 = ($x - $c[0]) * ($b[1] - $c[1]) - ($b[0] - $c[0]) * ($y - $c[1]);
    $d3 = ($x - $a[0]) * ($c[1] - $a[1]) - ($c[0] - $a[0]) * ($y - $a[1]);

    $hasNeg = ($d1 < 0) || ($d2 < 0) || ($d3 < 0);
    $hasPos = ($d1 > 0) || ($d2 > 0) || ($d3 > 0);

    return ! ($hasNeg && $hasPos);
}

function inRoundedSquare(float $x, float $y, float $r): bool
{
    $dx = abs($x - 0.5) - (0.5 - $r);
    $dy = abs($y - 0.5) - (0.5 - $r);

    if ($dx <= 0 || $dy <= 0) {
        return abs($x - 0.5) <= 0.5 && abs($y - 0.5) <= 0.5;
    }

    return ($dx ** 2 + $dy ** 2) <= $r ** 2;
}

/** @return array{0:int,1:int,2:int,3:int} RGBA */
function pixelAt(float $x, float $y): array
{
    $bg = [11, 22, 34, 255];
    $head = [245, 227, 195, 255];
    $shadow = [232, 208, 168, 255];
    $red = [216, 64, 44, 255];
    $beak = [233, 165, 58, 255];
    $beakDark = [201, 134, 42, 255];
    $eye = [20, 29, 41, 255];
    $glint = [255, 255, 255, 255];

    if (inCircle($x, $y, 0.595, 0.435, 0.022)) {
        return $glint;
    }
    if (inCircle($x, $y, 0.575, 0.452, 0.055)) {
        return $eye;
    }

    // head first, so the comb and beak sit on top of it
    if (inCircle($x, $y, 0.46, 0.56, 0.27)) {
        return $x < 0.42 && $y > 0.58 ? $shadow : $head;
    }
    if (inCircle($x, $y, 0.33, 0.31, 0.09)
        || inCircle($x, $y, 0.47, 0.24, 0.11)
        || inCircle($x, $y, 0.61, 0.31, 0.09)) {
        return $red;
    }
    if (inTriangle($x, $y, [0.70, 0.52], [0.95, 0.60], [0.70, 0.68])) {
        return $y > 0.61 ? $beakDark : $beak;
    }
    if (inCircle($x, $y, 0.72, 0.75, 0.09)) {
        return $red;
    }
    if (inRoundedSquare($x, $y, 0.22)) {
        return $bg;
    }

    return [0, 0, 0, 0];
}

function pngLikeBmp(int $size): string
{
    // 32-bit BGRA, bottom-up, as ICO expects (no compression).
    $xor = '';
    $and = '';

    $rows = [];
    $mask = [];

    for ($py = $size - 1; $py >= 0; $py--) {
        for ($px = 0; $px < $size; $px++) {
            $p = pixelAt(($px + 0.5) / $size, ($py + 0.5) / $size);
            $rows[] = chr($p[2]).chr($p[1]).chr($p[0]).chr($p[3]);
            // AND mask: 1 means transparent, 0 means opaque.
            $mask[$py] = ($mask[$py] ?? '')
                . ($p[3] === 0 ? "\1" : "\0");
        }
    }

    $xor = implode('', $rows);

    // AND mask rows are bottom-up, 1 bit per pixel, padded to 32 bits.
    $rowBytes = (int) (ceil($size / 32) * 4);
    for ($py = $size - 1; $py >= 0; $py--) {
        $bits = $mask[$py] ?? str_repeat("\1", $size);
        $packed = '';
        for ($byteIndex = 0; $byteIndex < $rowBytes; $byteIndex++) {
            $byte = 0;
            for ($bit = 0; $bit < 8; $bit++) {
                $index = $byteIndex * 8 + $bit;
                if ($index < $size && $bits[$index] === "\1") {
                    $byte |= 1 << (7 - $bit);
                }
            }
            $packed .= chr($byte);
        }
        $and .= $packed;
    }

    $header = pack('VVVvvVVVVVV', 40, $size, $size * 2, 1, 32, 0, strlen($xor) + strlen($and), 0, 0, 0, 0);

    return $header.$xor.$and;
}

$images = [];
foreach ($sizes as $size) {
    $images[$size] = pngLikeBmp($size);
}

$count = count($images);
$header = pack('vvv', 0, 1, $count);
$offset = 6 + 16 * $count;
$dir = '';
$body = '';

foreach ($images as $size => $data) {
    $dir .= pack('CCCCvvVV', $size % 256, $size % 256, 0, 0, 1, 32, strlen($data), $offset);
    $offset += strlen($data);
    $body .= $data;
}

$path = dirname(__DIR__).'/public/favicon.ico';
file_put_contents($path, $header.$dir.$body);

echo 'Wrote '.$path.' ('.count($sizes).' sizes, '.number_format(filesize($path)).' bytes)'.PHP_EOL;
