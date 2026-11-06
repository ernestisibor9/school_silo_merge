<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnologyFee extends Model
{
    protected $fillable = [
        'academic_session',
        'term',
        'fee_per_student',
        'status',
        'created_by',
        'updated_by',
        'published_at',
    ];

    protected $casts = [
        'fee_per_student' => 'decimal:2',
        'published_at' => 'datetime',
    ];
}
