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
        Schema::create('school_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('technology_fee_id');

            $table->string('school_id', 255);

            $table->string('academic_session', 50);
            $table->string('term', 50);

            $table->unsignedInteger('student_count');

            $table->decimal('fee_per_student', 12, 2);
            $table->decimal('total_amount', 14, 2);

            $table->enum('status', ['unpaid', 'paid'])
                ->default('unpaid');

            $table->timestamp('paid_at')->nullable();

            $table->unique(
                ['school_id', 'academic_session', 'term'],
                'school_bills_school_session_term_unique'
            );

            $table->index('technology_fee_id');
            $table->index('status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_bills');
    }
};
