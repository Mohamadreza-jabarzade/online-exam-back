<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'mobile',
        'name',
        'password',
        'otp_code',
        'otp_expires_at',
        'is_registered',
    ];

    protected $hidden = [
        'password',
        'otp_code',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'is_registered' => 'boolean',
    ];

    // تولید کد تایید ۴ رقمی
    public function generateOtp(): string
    {
        $this->otp_code = rand(1000, 9999);
        $this->otp_expires_at = now()->addMinutes(5);
        $this->save();

        return $this->otp_code;
    }

    // بررسی کد تایید
    public function isOtpValid(string $code): bool
    {
        return $this->otp_code === $code &&
            $this->otp_expires_at &&
            $this->otp_expires_at->isFuture();
    }

    // پاک کردن کد بعد از تایید
    public function clearOtp(): void
    {
        $this->otp_code = null;
        $this->otp_expires_at = null;
        $this->save();
    }
}
