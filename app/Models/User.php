<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'mobile',
        'username',
        'email',
        'password',
        'otp_code',
        'otp_expires_at',
        'mobile_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
        'otp_expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'mobile_verified_at' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * Generate OTP code for user
     */
    public function generateOtp(): string
    {
        $this->otp_code = rand(1000, 9999);
        $this->otp_expires_at = now()->addMinutes(5);
        $this->save();

        return $this->otp_code;
    }

    /**
     * Check if OTP is valid
     */
    public function isOtpValid(string $code): bool
    {
        return $this->otp_code === $code &&
            $this->otp_expires_at &&
            $this->otp_expires_at->isFuture();
    }

    /**
     * Clear OTP after verification
     */
    public function clearOtp(): void
    {
        $this->otp_code = null;
        $this->otp_expires_at = null;
        $this->save();
    }
}
