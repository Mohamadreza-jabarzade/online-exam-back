<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionBank extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    // کاربری که مالک این بانک سوال است
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // سوالات داخل این بانک
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
