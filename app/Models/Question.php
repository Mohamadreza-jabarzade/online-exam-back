<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Question extends Model
{
    use HasFactory;
    protected $fillable = [
        'question_bank_id',
        'creator_id',
        'content',
        'type'
    ];

    protected function casts(): array
    {
        return [
            'qwscore' => 'decimal:2',
        ];
    }

    // بانک سوالی که این سوال به آن تعلق دارد
    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class);
    }

    // طراح سوال
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    // گزینه‌های این سوال
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }

    // آزمون‌هایی که این سوال در آن‌ها استفاده شده است
    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_questions')
            ->withPivot('sort_order', 'id')
            ->withTimestamps();
    }
}
