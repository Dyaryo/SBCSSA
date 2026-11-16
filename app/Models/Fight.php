<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fight extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_BETTING_OPEN = 'betting_open';
    public const STATUS_BETTING_LOCKED = 'betting_locked';
    public const STATUS_LIVE = 'live';
    public const STATUS_SETTLED = 'settled';
    public const STATUS_VOID = 'void';
    public const STATUS_CANCELLED = 'cancelled';

    public const SIDE_RED = 'red';
    public const SIDE_BLUE = 'blue';
    public const SIDE_DRAW = 'draw';

    public const RESULT_MERON = 'meron';
    public const RESULT_WALA = 'wala';
    public const RESULT_DRAW = 'draw';
    public const RESULT_CANCELLED = 'cancelled';

    protected $fillable = [
        'event_id',
        'fight_no',
        'red_cock_id',
        'blue_cock_id',
        'scheduled_at',
        'odds_red',
        'odds_blue',
        'red_locked',
        'blue_locked',
        'stop_ratio',
        'status',
        'betting_opened_at',
        'betting_locked_at',
        'started_at',
        'ended_at',
        'winner',
        'result',
        'declared_by',
        'declared_at',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'betting_opened_at' => 'datetime',
            'betting_locked_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'declared_at' => 'datetime',
            'odds_red' => 'float',
            'odds_blue' => 'float',
            'red_locked' => 'boolean',
            'blue_locked' => 'boolean',
            'stop_ratio' => 'float',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function redCock()
    {
        return $this->belongsTo(Cock::class, 'red_cock_id');
    }

    public function blueCock()
    {
        return $this->belongsTo(Cock::class, 'blue_cock_id');
    }

    public function bets()
    {
        return $this->hasMany(Bet::class);
    }

    public function logs()
    {
        return $this->hasMany(FightEvent::class);
    }

    public function declaredBy()
    {
        return $this->belongsTo(User::class, 'declared_by');
    }

    public function isBettingOpen(): bool
    {
        return $this->status === self::STATUS_BETTING_OPEN;
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_SETTLED,
            self::STATUS_VOID,
            self::STATUS_CANCELLED,
        ], true);
    }

    public function oddsFor(string $side): float
    {
        return match ($side) {
            self::SIDE_RED => (float) $this->odds_red,
            self::SIDE_BLUE => (float) $this->odds_blue,
            self::SIDE_DRAW => $this->drawRatio(),
            default => 0.0,
        };
    }

    /**
     * The card's draw price — x7 unless the Game Master set another ratio.
     */
    public function drawRatio(): float
    {
        return $this->event?->drawRatio() ?? 7.0;
    }

    /**
     * The odds actually used to pay a bet. When the operator sets a stop ratio,
     * a payout can never exceed stake × ratio even if the posted odds are higher.
     */
    public function effectiveOddsFor(string $side): float
    {
        $odds = $this->oddsFor($side);

        // The stopper caps meron/wala payouts. Draw is a fixed price on the
        // card (x7), so clamping it to the stopper would delete the bet.
        if ($side === self::SIDE_DRAW) {
            return $odds;
        }

        $stop = $this->stop_ratio !== null ? (float) $this->stop_ratio : null;

        return $stop !== null && $stop > 0 ? min($odds, $stop) : $odds;
    }

    /**
     * True when the stopper is actively paying less than the posted price.
     * Draw is never clamped.
     */
    public function isCappedFor(string $side): bool
    {
        if ($side === self::SIDE_DRAW || ! $this->isStopRatioActive()) {
            return false;
        }

        return $this->effectiveOddsFor($side) < $this->oddsFor($side);
    }

    public function payoutFor(string $side, int $amount): int
    {
        // The epsilon keeps binary float noise from eating a whole credit:
        // 2.050 × 100 is 204.99999999999997 in IEEE doubles.
        return max($amount, (int) floor($amount * $this->effectiveOddsFor($side) + 1e-9));
    }

    /**
     * The pit can shut one side when the windows are being pushed all one way.
     * A locked side takes no new bets until the Game Master unlocks it.
     */
    public function isSideLocked(string $side): bool
    {
        return match ($side) {
            self::SIDE_RED => (bool) $this->red_locked,
            self::SIDE_BLUE => (bool) $this->blue_locked,
            default => false,
        };
    }

    public function isStopRatioActive(): bool
    {
        return $this->stop_ratio !== null && (float) $this->stop_ratio > 0;
    }

    /**
     * Reading of the two sabong sides. "meron" is the red corner, "wala" the
     * blue one; the database keeps red/blue so existing records stay readable.
     */
    public static function sideLabel(string $side): string
    {
        return match ($side) {
            self::SIDE_RED => 'meron',
            self::SIDE_BLUE => 'wala',
            self::SIDE_DRAW => 'draw',
            default => (string) $side,
        };
    }

    public static function sideTitle(string $side): string
    {
        return strtoupper(self::sideLabel($side));
    }

    public function resultLabel(): string
    {
        return match ($this->result) {
            self::RESULT_MERON => 'Meron',
            self::RESULT_WALA => 'Wala',
            self::RESULT_DRAW => 'Draw',
            self::RESULT_CANCELLED => 'Cancelled',
            default => $this->status === self::STATUS_SETTLED ? 'Settled' : ucfirst((string) $this->status),
        };
    }

    public function isDraw(): bool
    {
        return $this->result === self::RESULT_DRAW;
    }

    public function stakedCredits(?string $side = null): int
    {
        return (int) $this->bets()
            ->whereIn('status', [Bet::STATUS_ACTIVE, Bet::STATUS_WON, Bet::STATUS_LOST])
            ->when($side, fn ($q) => $q->where('side', $side))
            ->sum('amount_credits');
    }

    public function label(): string
    {
        return sprintf('Fight #%d — Meron vs Wala', $this->fight_no);
    }

    /**
     * Every side a window can take on this fight.
     */
    public static function sides(): array
    {
        return [self::SIDE_RED, self::SIDE_BLUE, self::SIDE_DRAW];
    }
}
