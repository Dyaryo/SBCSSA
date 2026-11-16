<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cock extends Model
{
    protected $fillable = [
        'name',
        'breed',
        'weight_grams',
        'owner_name',
        'band_number',
        'status',
    ];

    public function redFights()
    {
        return $this->hasMany(Fight::class, 'red_cock_id');
    }

    public function blueFights()
    {
        return $this->hasMany(Fight::class, 'blue_cock_id');
    }
}
