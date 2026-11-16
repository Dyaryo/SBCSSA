<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * The winners road (baccarat-style bead plate): a result runs down its column;
 * the same winner continues past the bottom as a "dragon tail" along the last
 * row. A different winner starts again at the top of the next column. Kept in
 * one place so the arena board and Game Master screens draw the same road.
 */
class WinnersRoad
{
    /**
     * @param  Collection<int, mixed>  $chronological  fights oldest first
     * @return array{rows:int, cols:int, cells:array<int, array<int, mixed>>, count:int}
     */
    public static function build(Collection $chronological, int $rows = 6, int $minCols = 16): array
    {
        $fights = $chronological->values();
        $rows = max(1, $rows);

        $cells = [];
        $row = 0;
        $col = 0;
        $last = null;
        $lastCol = 0;

        foreach ($fights as $fight) {
            $result = $fight->result ?? null;

            if ($last !== null) {
                if ($result === $last) {
                    if ($row < $rows - 1) {
                        $row++;
                    } else {
                        // The column is full: keep the same winner running
                        // sideways along the bottom row.
                        $col++;
                    }
                } else {
                    // The other side wins: start at the top of the next column.
                    $col++;
                    $row = 0;
                }
            }

            $cells[$row][$col] = $fight;
            $last = $result;
            $lastCol = max($lastCol, $col);
        }

        $cols = max($minCols, $lastCol + 1);

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $cells[$row][$col] ??= null;
            }
        }

        ksort($cells);

        foreach ($cells as &$rowCells) {
            ksort($rowCells);
        }
        unset($rowCells);

        return [
            'rows' => $rows,
            'cols' => $cols,
            'cells' => $cells,
            'count' => $fights->count(),
        ];
    }
}
