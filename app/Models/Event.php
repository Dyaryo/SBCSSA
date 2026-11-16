<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_REGULAR = 'regular';
    public const TYPE_DERBY = 'derby';

    protected $fillable = [
        'code',
        'name',
        'type',
        'entries',
        'planned_fights',
        'bomba_amount',
        'bomba_breakdown',
        'bomba_interval',
        'payout_display',
        'tv_total_bets',
        'venue',
        'game_master_id',
        'starts_at',
        'stop_ratio',
        'draw_ratio',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'stop_ratio' => 'float',
            'draw_ratio' => 'float',
            'planned_fights' => 'integer',
            'bomba_amount' => 'integer',
            'bomba_breakdown' => 'integer',
            'bomba_interval' => 'integer',
            'tv_total_bets' => 'integer',
            'entries' => 'array',
        ];
    }

    /**
     * Events carry the title the operator typed. The date-based label stays as
     * a fallback for events created before titles were mandatory.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value
                ?: 'Regular event · '.($this->starts_at?->format('d M Y') ?? $this->code ?? ''),
        );
    }

    public function gameMaster()
    {
        return $this->belongsTo(GameMasterProfile::class, 'game_master_id');
    }

    public function fights()
    {
        return $this->hasMany(Fight::class)->orderBy('fight_no');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isDerby(): bool
    {
        return $this->type === self::TYPE_DERBY;
    }

    /**
     * @return array<int,string>
     */
    public function entryNames(): array
    {
        return array_values(array_filter((array) $this->entries, fn ($entry) => trim((string) $entry) !== ''));
    }

    public function typeLabel(): string
    {
        return $this->isDerby() ? 'Derby' : 'Regular';
    }

    public const DISPLAY_DECIMALS = 'decimals';
    public const DISPLAY_WHOLE = 'whole';

    /**
     * The payout ceiling every fight in this event inherits.
     */
    public function hasStopRatio(): bool
    {
        return $this->stop_ratio !== null && (float) $this->stop_ratio > 0;
    }

    /**
     * What a draw ticket pays per credit. The paper board shows x7, so that is
     * the default when a card was made before this setting existed.
     */
    public function drawRatio(): float
    {
        return $this->draw_ratio !== null && (float) $this->draw_ratio > 0
            ? (float) $this->draw_ratio
            : 7.0;
    }
}
