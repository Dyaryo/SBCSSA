<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualWallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance_credits',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'balance_credits' => 'integer',
            'version' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }
}
