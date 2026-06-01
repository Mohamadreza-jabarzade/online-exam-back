<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chapter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject_id',
        'category_id',

        'name',
        'slug',
        'description',

        'page_start',
        'page_end',

        'is_active',
        'sort_order',

        'created_by',
    ];

    protected $casts = [
        'page_start' => 'integer',
        'page_end' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subject()
    {
        return $this->belongsTo(
            Subject::class
        );
    }

    public function category()
    {
        return $this->belongsTo(
            Category::class
        );
    }

    public function questions()
    {
        return $this->hasMany(
            Question::class
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}
