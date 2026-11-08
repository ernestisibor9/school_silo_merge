<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'technology_fee_id',
        'school_id',
        'academic_session',
        'term',
        'student_count',
        'fee_per_student',
        'total_amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'student_count' => 'integer',
        'fee_per_student' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(SchoolBillPayment::class);
    }
    
}
