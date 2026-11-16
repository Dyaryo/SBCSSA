<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMasterProfile extends Model
{
    protected $fillable = ['user_id', 'employee_no', 'contact', 'notes'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tellers()
    {
        return $this->hasMany(TellerProfile::class, 'game_master_id');
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'game_master_id');
    }

    public function displayName(): string
    {
        return $this->user?->name ?? 'Unassigned';
    }
}
