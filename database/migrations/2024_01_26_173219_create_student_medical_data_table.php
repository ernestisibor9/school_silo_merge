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
        Schema::create('student_medical_data', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->string('hospital');
            $table->string('blood');
            $table->string('geno');
            $table->string('hiv');
            $table->string('malaria');
            $table->string('typha');
            $table->string('tb');
            $table->string('heart');
            $table->string('liver');
            $table->string('vdrl');
            $table->string('hbp');
            $table->timestamps();

            //-Create Index Later
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_medical_data');
    }
};
