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
        Schema::create('school_bill_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_bill_id');

            $table->unsignedInteger('previous_student_count');
            $table->unsignedInteger('new_student_count');

            $table->decimal('previous_fee_per_student', 12, 2);
            $table->decimal('new_fee_per_student', 12, 2);

            $table->decimal('previous_total_amount', 14, 2);
            $table->decimal('new_total_amount', 14, 2);

            $table->enum('reason', [
                'fee_change',
                'learner_count_change',
                'fee_and_learner_count_change',
            ]);

            $table->unsignedBigInteger('changed_by')->nullable();

            $table->text('notes')->nullable();

            $table->index('school_bill_id');
            $table->index('reason');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_bill_adjustments');
    }
};
