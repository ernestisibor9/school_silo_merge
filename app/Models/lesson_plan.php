<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class lesson_plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'schid',
        'clsm',
        'date',
        'ssn',
        'trm',
        'sbj',
        'no_of_class',
        'average_age',
        'topic',
        'sub_topic',
        'time_from',
        'time_to',
        'duration',
        'learning_materials',
        'lesson_objectives',
        'plan_type',
        'weekly',
    ];

    protected $casts = [
        'sub_topic' => 'array',
        'learning_materials' => 'array',
        'lesson_objectives' => 'array',
        'date' => 'date',
        'time_from' => 'datetime:H:i',
        'time_to' => 'datetime:H:i',
    ];
}
