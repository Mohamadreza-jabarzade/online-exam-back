<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory,Notifiable;

    protected $fillable = [
        'mobile',
        'name',
        'family',
        'is_profile_completed',
        'role',
        'otp_code',
        'otp_expires_at'
    ];

    protected $hidden = [
        'otp_code'
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'is_profile_completed' => 'boolean'
    ];

    // هلپِر برای چک کردن نقش
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    // هلپِر برای پروفایل
    public function markProfileCompleted(): void
    {
        $this->update([
            'name' => $this->name,
            'family' => $this->family,
            'is_profile_completed' => true
        ]);
    }
}
