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
        Schema::create('school_billing_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_bill_id');
            $table->unsignedBigInteger('school_bill_adjustment_id')->nullable();

            $table->string('school_id', 255);
            $table->unsignedBigInteger('recipient_id');

            $table->enum('notification_type', [
                'fee_change',
                'learner_count_change',
                'fee_and_learner_count_change',
            ]);

            $table->string('title');
            $table->text('message');

            $table->timestamp('read_at')->nullable();

            $table->index('school_bill_id');
            $table->index('school_bill_adjustment_id');
            $table->index('school_id');
            $table->index('recipient_id');
            $table->index('notification_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_billing_notifications');
    }
};
