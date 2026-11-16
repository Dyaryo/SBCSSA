@php($roadCells = $cells ?? [])
@php($roadCols = $cols ?? 20)

<div class="road-panel">
    <div class="road-panel-head">
        <div>
            <strong>Winners road</strong>
            <span class="muted small">bead plate — oldest first, each column top to bottom</span>
        </div>
        <span class="muted small">{{ number_format($count ?? 0) }} result(s)</span>
    </div>

    <div class="road" style="--cols: {{ $roadCols }}">
        @foreach ($roadCells as $roadRow)
            @foreach ($roadRow as $cell)
                <div class="road-cell">
                    @if ($cell)
                        <span class="road-mark {{ $cell->result }}"
                              title="Fight #{{ $cell->fight_no }} — {{ strtoupper((string) $cell->result) }}"></span>
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>

    <div class="road-legend">
        <span><i class="road-mark meron"></i> meron</span>
        <span><i class="road-mark wala"></i> wala</span>
        <span><i class="road-mark draw"></i> draw</span>
        <span><i class="road-mark cancelled"></i> cancelled</span>
    </div>
</div>
