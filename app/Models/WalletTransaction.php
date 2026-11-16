<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    public const TYPE_SEED = 'seed';
    public const TYPE_BET_HOLD = 'bet_hold';
    public const TYPE_PAYOUT = 'payout';
    public const TYPE_VOID_REFUND = 'void_refund';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_SHIFT_CORRECTION = 'shift_correction';

    public const DIRECTION_CREDIT = 'credit';
    public const DIRECTION_DEBIT = 'debit';

    public $timestamps = false;

    protected $fillable = [
        'wallet_id',
        'user_id',
        'fight_id',
        'bet_id',
        'type',
        'direction',
        'amount_credits',
        'balance_after',
        'reference_no',
        'idempotency_key',
        'performed_by',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_credits' => 'integer',
            'balance_after' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function wallet()
    {
        return $this->belongsTo(VirtualWallet::class, 'wallet_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function fight()
    {
        return $this->belongsTo(Fight::class);
    }

    public function bet()
    {
        return $this->belongsTo(Bet::class);
    }
}
