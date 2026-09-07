<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LessonPlanOption extends Model
{
    protected $table = 'lesson_plan_options';

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | SCHOOL / CLASS INFORMATION
        |--------------------------------------------------------------------------
        */

        'schid',
        'clsm',
        'ssn',
        'trm',
        'sbj',

        /*
        |--------------------------------------------------------------------------
        | PLAN TYPE
        |--------------------------------------------------------------------------
        */

        'plan_type',
        'weekly',

        /*
        |--------------------------------------------------------------------------
        | LESSON INFORMATION
        |--------------------------------------------------------------------------
        */

        'date',
        'time_from',
        'time_to',
        'period',
        'duration',
        'sex',
        'topic',

        /*
        |--------------------------------------------------------------------------
        | LESSON CONTENT
        |--------------------------------------------------------------------------
        */

        'sub_topic',
        'lesson_objectives',
        'instructional_sources_material',

        /*
        |--------------------------------------------------------------------------
        | STEP I
        |--------------------------------------------------------------------------
        */

        'step1_previous_knowledge',
        'step1_mode',
        'step1_teacher_activities',
        'step1_student_activities',

        /*
        |--------------------------------------------------------------------------
        | STEP II
        |--------------------------------------------------------------------------
        */

        'step2_mode',
        'step2_teacher_activities',
        'step2_student_activities',

        /*
        |--------------------------------------------------------------------------
        | STEP III
        |--------------------------------------------------------------------------
        */

        'step3_mode',
        'step3_teacher_activities',
        'step3_student_activities',

        /*
        |--------------------------------------------------------------------------
        | STEP IV
        |--------------------------------------------------------------------------
        */

        'step4_mode',
        'step4_teacher_activities',
        'step4_student_activities',

        /*
        |--------------------------------------------------------------------------
        | STEP V
        |--------------------------------------------------------------------------
        */

        'step5_mode',
        'step5_teacher_activities',
        'step5_student_activities',

        /*
        |--------------------------------------------------------------------------
        | LESSON CONCLUSION
        |--------------------------------------------------------------------------
        */

        'summary',
        'conclusion',
        'assignment',
        'reference',

        /*
        |--------------------------------------------------------------------------
        | SUBJECT HEAD
        |--------------------------------------------------------------------------
        */

        'topic_tally',
        'other_comments',
        'subject_head_signature',
        'subject_head_signature_date',
    ];

    protected $casts = [

        /*
        |--------------------------------------------------------------------------
        | DATES
        |--------------------------------------------------------------------------
        */

        'date' => 'date',
        'subject_head_signature_date' => 'date',

        /*
        |--------------------------------------------------------------------------
        | REPEATABLE FIELDS
        |--------------------------------------------------------------------------
        */

        'sub_topic' => 'array',

        'lesson_objectives' => 'array',

        'instructional_sources_material' => 'array',

        'step1_previous_knowledge' => 'array',
        'step1_teacher_activities' => 'array',
        'step1_student_activities' => 'array',

        'step2_teacher_activities' => 'array',
        'step2_student_activities' => 'array',

        'step3_teacher_activities' => 'array',
        'step3_student_activities' => 'array',

        'step4_teacher_activities' => 'array',
        'step4_student_activities' => 'array',

        'step5_teacher_activities' => 'array',
        'step5_student_activities' => 'array',

        'summary' => 'array',
        'conclusion' => 'array',
        'assignment' => 'array',
        'reference' => 'array',
    ];

    // Append custom attribute to JSON responses
    protected $appends = ['subject_head_signature_url'];

    public function getSubjectHeadSignatureUrlAttribute()
    {
        return $this->subject_head_signature 
            ? asset($this->subject_head_signature) 
            : null;
    }
}
