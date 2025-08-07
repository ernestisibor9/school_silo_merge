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
        Schema::create('student_parent_data', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->string('fname');
            $table->string('lname');
            $table->string('mname');
            $table->string('sex');
            $table->string('phn');
            $table->string('eml');
            $table->string('relation');
            $table->string('job');
            $table->string('addr');
            $table->string('origin');
            $table->string('residence');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_parent_data');
    }
};
