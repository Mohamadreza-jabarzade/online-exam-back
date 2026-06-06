<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ExamQuestion extends Pivot
{
    use HasFactory;
    // نام جدولی که این مدل به آن اشاره دارد
    protected $table = 'exam_questions';

    // چون در مایگریشن id و timestamps داری:
    public $incrementing = true;

    protected $fillable = [
        'exam_id',
        'question_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
