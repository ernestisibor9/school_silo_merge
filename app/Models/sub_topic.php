<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sub_topic extends Model
{
    use HasFactory;
    
        protected $fillable = [
        'topic_id',
        'title',
        'sub_topic_content_one',
        'sub_topic_content_two',
        'sub_topic_content_three',
        'sub_topic_content_four',
        'sub_topic_content_five',
        'sub_topic_content_six',
        'sub_topic_content_seven',
        'sub_topic_content_eight',
        'sub_topic_content_nine',
        'sub_topic_content_ten',
        'sub_topic_evaluation'
    ];

    protected $casts = [
        'sub_topic_evaluation' => 'array',
    ];

    public function topic()
    {
        return $this->belongsTo(topic::class);
    }
}
