<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolBillPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_bill_id',
        'payment_reference',
        'receipt_number',
        'paystack_transaction_id',
        'amount',
        'currency',
        'status',
        'channel',
        'gateway_response',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'verified_at' => 'datetime',
    ];

    public function schoolBill(): BelongsTo
    {
        return $this->belongsTo(SchoolBill::class);
    }
}
