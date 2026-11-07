<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolBillAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_bill_id',
        'previous_student_count',
        'new_student_count',
        'previous_fee_per_student',
        'new_fee_per_student',
        'previous_total_amount',
        'new_total_amount',
        'reason',
        'changed_by',
        'notes',
    ];

    protected $casts = [
        'previous_student_count' => 'integer',
        'new_student_count' => 'integer',
        'previous_fee_per_student' => 'decimal:2',
        'new_fee_per_student' => 'decimal:2',
        'previous_total_amount' => 'decimal:2',
        'new_total_amount' => 'decimal:2',
    ];

    public function schoolBill(): BelongsTo
    {
        return $this->belongsTo(SchoolBill::class);
    }
}
