<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workstation extends Model
{
    protected $fillable = [
        'code',
        'label',
        'device_uuid',
        'assigned_teller_id',
        'approved_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function assignedTeller()
    {
        return $this->belongsTo(TellerProfile::class, 'assigned_teller_id');
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isBound(): bool
    {
        return $this->device_uuid !== null && $this->device_uuid !== '';
    }
}
