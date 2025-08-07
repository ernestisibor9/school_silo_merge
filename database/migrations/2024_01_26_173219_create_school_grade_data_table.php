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
        Schema::create('school_grade_data', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->string('a_h');
            $table->string('a_l');
            $table->string('b2_h');
            $table->string('b2_l');
            $table->string('b3_h');
            $table->string('b3_l');
            $table->string('c4_h');
            $table->string('c4_l');
            $table->string('c5_h');
            $table->string('c5_l');
            $table->string('c6_h');
            $table->string('c6_l');
            $table->string('d7_h');
            $table->string('d7_l');
            $table->string('e8_h');
            $table->string('e8_l');
            $table->string('f_h');
            $table->string('f_l');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_grade_data');
    }
};
