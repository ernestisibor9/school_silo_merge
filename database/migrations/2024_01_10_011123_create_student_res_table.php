<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_res', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->string('stat');
            $table->text('com');
            $table->string('stid');
            $table->string('schid');
            $table->string('ssn');
            $table->string('trm');
            $table->string('clsm');
            $table->string('clsa');
            $table->integer('pos');
            $table->decimal('avg', 5, 2);  // Allows values from -999.99 to 999.99
            $table->decimal('cavg', 5, 2);
            $table->timestamps();

            // For queries based on stid
            $table->index('stid');
            // For queries based on schid
            $table->index('schid');
            // For queries based on ssn
            $table->index('ssn');
            // For queries based on trm
            $table->index('trm');
            // For queries based on clsm
            $table->index('clsm');
            // For queries based on clsa
            $table->index('clsa');
            // For queries based on stat
            $table->index('stat');
            // For queries based on pos
            $table->index('pos');
            // For queries based on avg
            $table->index('avg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_res');
    }
};
