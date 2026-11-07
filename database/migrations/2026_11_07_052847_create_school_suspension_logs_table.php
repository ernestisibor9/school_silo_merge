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
        Schema::create('school_suspension_logs', function (Blueprint $table) {
            $table->id();
            $table->string('school_id', 255);

            $table->enum('action', [
                'suspended',
                'reactivated',
            ]);

            $table->unsignedBigInteger('performed_by')->nullable();

            $table->text('reason')->nullable();

            $table->index('school_id');
            $table->index('action');
            $table->index('performed_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_suspension_logs');
    }
};
