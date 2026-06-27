<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory,Notifiable;

    protected $fillable = [
        'mobile',
        'role',
        'otp_code',
        'otp_expires_at',
        'last_otp_sent_at',
        'can_create_exam',
    ];

    protected $hidden = [
        'otp_code',
    ];

    protected function casts(): array
    {
        return [
            'otp_expires_at' => 'datetime',
            'last_otp_sent_at' => 'datetime',
            'can_create_exam' => 'boolean',
        ];
    }

    // آزمون‌هایی که این کاربر ساخته است
    public function createdExams(): HasMany
    {
        return $this->hasMany(Exam::class, 'creator_id');
    }

    // بانک‌های سوالی که این کاربر ایجاد کرده است
    public function questionBanks(): HasMany
    {
        return $this->hasMany(QuestionBank::class);
    }

    // سوالاتی که این کاربر طراح آن‌ها بوده است
    public function createdQuestions(): HasMany
    {
        return $this->hasMany(Question::class, 'creator_id');
    }

    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

}
