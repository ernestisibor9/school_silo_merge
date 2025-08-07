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
        Schema::create('student_basic_data', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->string('dob');
            $table->string('sex');
            $table->string('height');
            $table->string('country');
            $table->string('state');
            $table->string('lga');
            $table->text('addr');
            $table->timestamps();
            
            // For queries based on sex
            $table->index('sex');
            // For queries based on country
            $table->index('country');
            // For queries based on state
            $table->index('state');
            // For queries based on lga
            $table->index('lga');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_basic_data');
    }
};
