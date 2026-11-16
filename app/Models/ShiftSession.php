<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftSession extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_FORCE_CLOSED = 'force_closed';

    protected $fillable = [
        'teller_id',
        'workstation_id',
        'opened_at',
        'closed_at',
        'opening_note',
        'closing_note',
        'total_bets_count',
        'total_amount_credits',
        'status',
        'closed_reason',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'total_bets_count' => 'integer',
            'total_amount_credits' => 'integer',
        ];
    }

    public function teller()
    {
        return $this->belongsTo(TellerProfile::class, 'teller_id');
    }

    public function workstation()
    {
        return $this->belongsTo(Workstation::class);
    }

    public function bets()
    {
        return $this->hasMany(Bet::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
