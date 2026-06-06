<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'show_result' => 'boolean',
            'show_correct_answers' => 'boolean',
            'random_questions' => 'boolean',
            'random_options' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    // کاربری که آزمون را ساخته است
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    // سوالات مربوط به این آزمون
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->withPivot('sort_order', 'id')
            ->withTimestamps()
            ->orderByPivot('sort_order', 'asc');
    }
}
