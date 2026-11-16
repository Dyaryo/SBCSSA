<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_SUPERVISOR = 'supervisor';
    public const ROLE_GAME_MASTER = 'game_master';
    public const ROLE_TELLER = 'teller';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'status',
        'created_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function gameMasterProfile()
    {
        return $this->hasOne(GameMasterProfile::class);
    }

    public function tellerProfile()
    {
        return $this->hasOne(TellerProfile::class);
    }

    public function wallet()
    {
        return $this->hasOne(VirtualWallet::class);
    }

    public function walletOrCreate(): VirtualWallet
    {
        return $this->wallet()->firstOrCreate([], [
            'balance_credits' => 0,
            'version' => 0,
        ]);
    }

    public function isSupervisor(): bool
    {
        return $this->role === self::ROLE_SUPERVISOR;
    }

    public function isGameMaster(): bool
    {
        return $this->role === self::ROLE_GAME_MASTER;
    }

    public function isTeller(): bool
    {
        return $this->role === self::ROLE_TELLER;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function openShift(): ?ShiftSession
    {
        if (! $this->isTeller()) {
            return null;
        }

        return $this->tellerProfile?->shifts()
            ->where('status', ShiftSession::STATUS_OPEN)
            ->latest('id')
            ->first();
    }

    public function creditBalance(): int
    {
        return (int) ($this->wallet?->balance_credits ?? 0);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_SUPERVISOR => 'Supervisor',
            self::ROLE_GAME_MASTER => 'Game Master',
            default => 'POS Teller',
        };
    }
}
