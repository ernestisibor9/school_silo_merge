<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolBillingNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_bill_id',
        'school_bill_adjustment_id',
        'school_id',
        'recipient_id',
        'notification_type',
        'title',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function schoolBill(): BelongsTo
    {
        return $this->belongsTo(SchoolBill::class);
    }

    public function schoolBillAdjustment(): BelongsTo
    {
        return $this->belongsTo(SchoolBillAdjustment::class);
    }
}
