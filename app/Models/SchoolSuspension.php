<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSuspension extends Model
{
    protected $table = 'school_suspensions';

    protected $fillable = [
        'school_id',
        'academic_session',
        'term',
        'reason',
        'status',
        'suspended_by',
        'suspended_at',
        'reactivated_by',
        'reactivated_at',
        'reactivation_reason',
    ];

    protected $casts = [
        'suspended_at' => 'datetime',
        'reactivated_at' => 'datetime',
    ];
}