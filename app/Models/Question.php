<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $fillable = [

        'type',
        'question_text',
        'question_image',

        'chapter_id',

        'page_number',

        'time_limit',

        'difficulty',
        'importance',

        'correct_option_order',

        'explanation',

        'status',

        'created_by',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'time_limit' => 'integer',
        'correct_option_order' => 'integer',
    ];

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function options()
    {
        return $this->hasMany(
            QuestionOption::class
        )->orderBy('order');
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

}
