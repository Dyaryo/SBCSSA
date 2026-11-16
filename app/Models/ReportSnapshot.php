<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'scope',
        'scope_ref_id',
        'period_start',
        'period_end',
        'payload',
        'payload_hash',
        'prev_hash',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
            'generated_at' => 'datetime',
        ];
    }
}
