<?php

namespace App\Support;

/**
 * Dependency-free Code 39 barcode renderer. The slips are printed on a LAN with
 * no internet access, so the bars are drawn as plain SVG instead of calling an
 * online barcode service. Code 39 is deliberately forgiving: any handheld
 * scanner reads it without configuration, and the slip codes are already
 * uppercase A-Z, 0-9 and dashes.
 */
class Barcode
{
    /** Wide:narrow ratio of 3 keeps the bars crisp on 80 mm paper. */
    private const WIDE = 3;

    private const QUIET_MODULES = 10;

    /**
     * Nine elements per character, alternating bar/space, starting with a bar.
     */
    private const PATTERNS = [
        '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
        '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
        '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn',
        'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw', 'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw',
        'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn', 'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn',
        'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn', 'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww',
        'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww', 'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn',
        'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn', 'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn',
        'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw', 'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw',
        'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
        '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
        '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
    ];

    /**
     * @return string SVG markup, black bars on white
     */
    public static function svg(string $data, int $height = 46, float $unit = 1.6): string
    {
        $text = strtoupper(trim(preg_replace('/[^0-9A-Z\-. $\/+%]/', '', $data) ?? ''));
        $text = $text === '' ? 'BET' : $text;

        $elements = [];

        foreach (str_split('*'.$text.'*') as $character) {
            $pattern = self::PATTERNS[$character] ?? null;

            if ($pattern === null) {
                continue;
            }

            foreach (str_split($pattern) as $index => $width) {
                $elements[] = [
                    'bar' => $index % 2 === 0,
                    'width' => $width === 'w' ? self::WIDE : 1,
                ];
            }

            // One narrow space between characters.
            $elements[] = ['bar' => false, 'width' => 1];
        }

        $modules = self::QUIET_MODULES * 2;

        foreach ($elements as $element) {
            $modules += $element['width'];
        }

        $width = round($modules * $unit, 2);
        $x = self::QUIET_MODULES * $unit;
        $bars = '';

        foreach ($elements as $element) {
            $barWidth = $element['width'] * $unit;

            if ($element['bar']) {
                $bars .= '<rect x="'.round($x, 2).'" y="0" width="'.round($barWidth, 2).'" height="'.$height.'"/>';
            }

            $x += $barWidth;
        }

        return '<svg class="barcode" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$width.' '.$height.'"'
            .' preserveAspectRatio="none" role="img" aria-label="Barcode '.e($text).'" data-barcode="'.e($text).'">'
            .'<rect width="'.$width.'" height="'.$height.'" fill="#fff"/>'
            .'<g fill="#111">'.$bars.'</g>'
            .'</svg>';
    }
}
