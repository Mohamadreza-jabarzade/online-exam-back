<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str; // اضافه کردن این خط

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'title',
        'description',
        'duration_minutes',
        'show_result',
        'show_correct_answers',
        'random_questions',
        'random_options',
        'status',
        'start_time',
        'end_time',
        'published_at',
        'uuid', // اضافه شدن به fillable
    ];

    /**
     * رویدادهای خودکار مدل
     */
    protected static function booted()
    {
        // هنگام ساخت آزمون جدید، به صورت خودکار UUID تولید می‌شود
        static::creating(function ($exam) {
            $exam->uuid = (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'show_result' => 'boolean',
            'show_correct_answers' => 'boolean',
            'random_questions' => 'boolean',
            'random_options' => 'boolean',
            'published_at' => 'datetime',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->withPivot('sort_order', 'id')
            ->withTimestamps()
            ->orderByPivot('sort_order', 'asc');
    }
}
