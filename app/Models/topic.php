<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class topic extends Model
{
    use HasFactory;
    
        protected $fillable = [
        'lesson_note_id',
        'title',
        'references',
        'content',
        'general_evaluation',
        'weekend_assignment',
        'theory'
    ];

    protected $casts = [
        'references' => 'array',
        'content' => 'array',
        'general_evaluation' => 'array',
        'weekend_assignment' => 'array',
        'theory' => 'array',
    ];

    public function lessonNote()
    {
        return $this->belongsTo(lesson_note::class);
    }

    public function subTopics()
    {
        return $this->hasMany(sub_topic::class);
    }
}
