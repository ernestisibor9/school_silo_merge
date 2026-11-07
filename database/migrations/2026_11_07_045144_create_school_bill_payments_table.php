<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_bill_payments', function (Blueprint $table) {
            $table->id();
                        $table->unsignedBigInteger('school_bill_id');

            $table->string('payment_reference', 100)->unique();

            $table->unsignedBigInteger('paystack_transaction_id')->nullable();

            $table->decimal('amount', 14, 2);

            $table->string('currency', 3)->default('NGN');

            $table->enum('status', [
                'pending',
                'successful',
                'failed',
            ])->default('pending');

            $table->string('channel', 50)->nullable();

            $table->json('gateway_response')->nullable();

            $table->timestamp('verified_at')->nullable();

            $table->index('school_bill_id');
            $table->index('status');
            $table->index('paystack_transaction_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_bill_payments');
    }
};
