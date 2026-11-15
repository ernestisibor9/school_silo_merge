<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_suspensions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('school_id', 50);

            // Academic period affected.
            $table->string('academic_session', 9);
            $table->string('term', 30);

            // Suspension details.
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('suspended');

            $table->unsignedBigInteger('suspended_by')->nullable();
            $table->timestamp('suspended_at')->useCurrent();

            // Reactivation details.
            $table->unsignedBigInteger('reactivated_by')->nullable();
            $table->timestamp('reactivated_at')->nullable();
            $table->text('reactivation_reason')->nullable();

            $table->timestamps();

            $table->index(
                ['school_id', 'academic_session', 'term', 'status'],
                'school_suspension_period_index'
            );

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_suspensions');
    }
};