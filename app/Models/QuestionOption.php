<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
    use HasFactory;
    protected $fillable = [
        'question_id',
        'content',
        'score',
        'is_correct',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // سوالی که این گزینه متعلق به آن است
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
