<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FightEvent extends Model
{
    public $timestamps = false;

    protected $table = 'fight_events';

    protected $fillable = [
        'fight_id',
        'kind',
        'round_no',
        'note',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'round_no' => 'integer',
        ];
    }

    public function fight()
    {
        return $this->belongsTo(Fight::class);
    }
}
