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
        Schema::create('student_sub_res', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->string('stid');
            $table->string('schid');
            $table->string('ssn');
            $table->string('trm');
            $table->string('clsm');
            $table->string('clsa');
            $table->string('sbj');
            $table->integer('pos');
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
            // For queries based on sbj
            $table->index('sbj');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_sub_res');
    }
};
