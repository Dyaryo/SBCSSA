<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TellerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'teller_code',
        'game_master_id',
        'workstation_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gameMaster()
    {
        return $this->belongsTo(GameMasterProfile::class, 'game_master_id');
    }

    /**
     * Tills that belong to this Game Master's book: the ones assigned to them
     * plus the unassigned pit tills, which work whoever is on duty.
     */
    public function scopeVisibleToGameMaster($query, int $gameMasterId)
    {
        return $query->where(function ($q) use ($gameMasterId) {
            $q->where('game_master_id', $gameMasterId)->orWhereNull('game_master_id');
        });
    }

    public function workstation()
    {
        return $this->belongsTo(Workstation::class);
    }

    public function shifts()
    {
        return $this->hasMany(ShiftSession::class, 'teller_id');
    }

    public function bets()
    {
        return $this->hasMany(Bet::class, 'teller_id');
    }

    public function openShift(): ?ShiftSession
    {
        return $this->shifts()->where('status', ShiftSession::STATUS_OPEN)->latest('id')->first();
    }

    public function displayName(): string
    {
        return $this->user?->name ?? $this->teller_code;
    }
}
