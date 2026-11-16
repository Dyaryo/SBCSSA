<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\User;
use App\Services\QrService;
use App\Services\ShiftSlipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reads the QR code off the rendered receipt the way a scanner would: rasterise
 * the SVG into modules, read the format information, unmask, walk the data
 * modules and decode the payload. If anyone changes the printed code, the
 * encoding, or the SVG geometry, this test stops passing.
 */
class ScannableTicketTest extends TestCase
{
    use RefreshDatabase;

    private const ALNUM = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';

    public function test_a_scanner_reads_the_slip_code_off_the_printed_ticket(): void
    {
        $this->seed();

        $user = User::where('username', 'pos01')->firstOrFail();
        $bet = Bet::whereHas('teller', fn ($q) => $q->where('teller_code', 'POS 001'))->firstOrFail();

        $code = app(ShiftSlipService::class)->code('BET', (int) $bet->id, (int) $bet->amount_credits, (string) $bet->placed_at);

        $html = $this->actingAs($user)
            ->get('/pos/bets/'.$bet->id.'/receipt')
            ->assertOk()
            ->getContent();

        // The ticket also carries a 1D barcode now, so pick the QR (path-based)
        // SVG out of the page rather than assuming it is the first one.
        preg_match_all('/<svg\b.*?<\/svg>/s', $html, $svgs);

        $qr = null;

        foreach ($svgs[0] as $candidate) {
            if (str_contains($candidate, '<path')) {
                $qr = $candidate;
                break;
            }
        }

        $this->assertNotNull($qr, 'The ticket must carry a QR code.');
        $this->assertSame($code, $this->scan($qr), 'A scanner reading the printed QR must get the slip code back.');
    }

    public function test_the_decoder_reads_a_payload_rather_than_returning_the_expected_string(): void
    {
        // Guards the guard: the same decoder must recover whatever was encoded.
        $svg = app(QrService::class)->svg('MERON-12345', 120, 2);

        $this->assertSame('MERON-12345', $this->scan($svg));
    }

    /**
     * Decode an SVG QR code back to its text payload.
     */
    private function scan(string $svg): string
    {
        [$size, $matrix] = $this->modules($svg);

        $version = (int) (($size - 17) / 4);

        if ($version < 1 || $version > 2) {
            $this->fail("This decoder understands QR versions 1-2; the ticket rendered version {$version} ({$size} modules).");
        }

        $function = $this->functionModules($size, $version);
        [$eccBits, $mask] = $this->formatInfo($matrix);

        $dataCodewords = [
            1 => [1 => 19, 0 => 16, 3 => 13, 2 => 9],
            2 => [1 => 34, 0 => 28, 3 => 22, 2 => 16],
        ][$version][$eccBits] ?? null;

        if ($dataCodewords === null) {
            $this->fail('Unknown QR error-correction level in the format information.');
        }

        $bits = $this->dataBits($matrix, $function, $mask, $size);

        return $this->parsePayload(array_slice($bits, 0, $dataCodewords * 8));
    }

    /**
     * Rasterise the SVG path into a boolean module grid, using the even-odd
     * fill rule the renderer relies on.
     *
     * @return array{0:int,1:array<int,array<int,bool>>}
     */
    private function modules(string $svg): array
    {
        $viewBox = preg_match('/viewBox="0 0 ([\d.]+) ([\d.]+)"/', $svg, $vb);
        $scale = preg_match('/scale\(([\d.]+)\)/', $svg, $sc);
        $translate = preg_match('/translate\(([\d.]+),([\d.]+)\)/', $svg, $tr);
        $path = preg_match('/<path[^>]*d="([^"]*)"/s', $svg, $pd);

        $this->assertTrue($viewBox === 1 && $scale === 1 && $path === 1, 'The QR SVG geometry changed unexpectedly.');

        $units = (float) $vb[1] / (float) $sc[1];
        $margin = $translate ? (int) round((float) $tr[1]) : 0;
        $size = (int) round($units) - 2 * $margin;

        preg_match_all('/([MLZ])([^MLZ]*)/', $pd[1], $segments, PREG_SET_ORDER);

        $polygons = [];
        $current = [];

        foreach ($segments as $segment) {
            if ($segment[1] === 'M' || $segment[1] === 'L') {
                preg_match_all('/(-?[\d.]+)\s+(-?[\d.]+)/', $segment[2], $points, PREG_SET_ORDER);

                foreach ($points as $point) {
                    if ($segment[1] === 'M' && $current !== []) {
                        $polygons[] = $current;
                        $current = [];
                    }
                    $current[] = [(float) $point[1], (float) $point[2]];
                }
            } elseif ($current !== []) {
                $polygons[] = $current;
                $current = [];
            }
        }

        if ($current !== []) {
            $polygons[] = $current;
        }

        $matrix = [];

        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                $x = $col + 0.5;
                $y = $row + 0.5;
                $inside = false;

                foreach ($polygons as $polygon) {
                    $count = count($polygon);

                    for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
                        [$xi, $yi] = $polygon[$i];
                        [$xj, $yj] = $polygon[$j];

                        if ((($yi > $y) !== ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi)) {
                            $inside = ! $inside;
                        }
                    }
                }

                $matrix[$row][$col] = $inside;
            }
        }

        return [$size, $matrix];
    }

    /**
     * @return array{0:int,1:int} error-correction bits, mask pattern
     */
    private function formatInfo(array $matrix): array
    {
        $bits = 0;

        foreach ([
            [0, 8], [1, 8], [2, 8], [3, 8], [4, 8], [5, 8], [7, 8], [8, 8],
            [8, 7], [8, 5], [8, 4], [8, 3], [8, 2], [8, 1], [8, 0],
        ] as [$x, $y]) {
            $bits = ($bits << 1) | ($matrix[$y][$x] ? 1 : 0);
        }

        $bits ^= 0x5412;

        return [($bits >> 13) & 0x03, ($bits >> 10) & 0x07];
    }

    /**
     * @return array<int,array<int,bool>> true where a module is not data
     */
    private function functionModules(int $size, int $version): array
    {
        $function = array_fill(0, $size, array_fill(0, $size, false));

        $mark = function (int $row0, int $col0, int $height, int $width) use (&$function): void {
            for ($row = $row0; $row < $row0 + $height; $row++) {
                for ($col = $col0; $col < $col0 + $width; $col++) {
                    $function[$row][$col] = true;
                }
            }
        };

        // Finder patterns with their separators.
        $mark(0, 0, 8, 8);
        $mark(0, $size - 8, 8, 8);
        $mark($size - 8, 0, 8, 8);

        // Timing patterns.
        $mark(6, 0, 1, $size);
        $mark(0, 6, $size, 1);

        // Format information around the finders, plus the dark module.
        $mark(8, 0, 1, 9);
        $mark(8, $size - 8, 1, 8);
        $mark(0, 8, 9, 1);
        $mark($size - 7, 8, 7, 1);

        // Version 2 carries one alignment pattern in the bottom-right corner.
        if ($version > 1) {
            $mark($size - 9, $size - 9, 5, 5);
        }

        return $function;
    }

    /**
     * Walk the module grid in QR order, unmasking as we go.
     *
     * @return array<int,bool>
     */
    private function dataBits(array $matrix, array $function, int $mask, int $size): array
    {
        $bits = [];
        $upwards = true;

        for ($column = $size - 1; $column > 0; $column -= 2) {
            if ($column === 6) {
                $column--;
            }

            for ($i = 0; $i < $size; $i++) {
                $row = $upwards ? $size - 1 - $i : $i;

                for ($k = 0; $k < 2; $k++) {
                    $col = $column - $k;

                    if ($function[$row][$col]) {
                        continue;
                    }

                    $bit = $matrix[$row][$col];

                    if ($this->isMasked($mask, $row, $col)) {
                        $bit = ! $bit;
                    }

                    $bits[] = $bit;
                }
            }

            $upwards = ! $upwards;
        }

        return $bits;
    }

    private function isMasked(int $mask, int $row, int $col): bool
    {
        return match ($mask) {
            0 => ($row + $col) % 2 === 0,
            1 => $row % 2 === 0,
            2 => $col % 3 === 0,
            3 => ($row + $col) % 3 === 0,
            4 => (intdiv($row, 2) + intdiv($col, 3)) % 2 === 0,
            5 => ($row * $col) % 2 + ($row * $col) % 3 === 0,
            6 => (($row * $col) % 2 + ($row * $col) % 3) % 2 === 0,
            7 => (($row + $col) % 2 + ($row * $col) % 3) % 2 === 0,
            default => false,
        };
    }

    /**
     * @param  array<int,bool>  $bits
     */
    private function parsePayload(array $bits): string
    {
        $position = 0;

        $read = function (int $length) use (&$position, $bits): int {
            $value = 0;

            for ($i = 0; $i < $length; $i++) {
                $value = ($value << 1) | (int) $bits[$position + $i];
            }

            $position += $length;

            return $value;
        };

        $mode = $read(4);

        if ($mode === 0b0010) {
            $characters = $read(9);
            $text = '';

            while (strlen($text) < $characters) {
                if ($characters - strlen($text) >= 2) {
                    $pair = $read(11);
                    $text .= self::ALNUM[intdiv($pair, 45)].self::ALNUM[$pair % 45];
                } else {
                    $text .= self::ALNUM[$read(6)];
                }
            }

            return $text;
        }

        if ($mode === 0b0100) {
            $bytes = $read(8);
            $text = '';

            for ($i = 0; $i < $bytes; $i++) {
                $text .= chr($read(8));
            }

            return $text;
        }

        $this->fail('The QR payload did not start with an alphanumeric or byte segment (mode '.$mode.').');
    }
}
