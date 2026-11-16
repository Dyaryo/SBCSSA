<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bet extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_WON = 'won';
    public const STATUS_LOST = 'lost';
    public const STATUS_VOID = 'void';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'fight_id',
        'teller_id',
        'shift_session_id',
        'side',
        'amount_credits',
        'odds_at_placement',
        'potential_payout',
        'status',
        'placed_at',
        'settled_at',
        'paid_out_at',
        'paid_out_by',
        'voided_by',
        'void_reason',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'amount_credits' => 'integer',
            'potential_payout' => 'integer',
            'odds_at_placement' => 'float',
            'placed_at' => 'datetime',
            'settled_at' => 'datetime',
            'paid_out_at' => 'datetime',
        ];
    }

    public function fight()
    {
        return $this->belongsTo(Fight::class);
    }

    public function teller()
    {
        return $this->belongsTo(TellerProfile::class, 'teller_id');
    }

    public function shift()
    {
        return $this->belongsTo(ShiftSession::class, 'shift_session_id');
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function paidOutBy()
    {
        return $this->belongsTo(User::class, 'paid_out_by');
    }

    public function isPaidOut(): bool
    {
        return $this->paid_out_at !== null;
    }
}
