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
        Schema::create('staff_prof_data', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->string('grad_date');
            $table->string('univ');
            $table->string('area');
            $table->string('qual');
            $table->string('trcn');
            $table->string('hqual');
            $table->string('place_first_appt');
            $table->string('last_employment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_prof_data');
    }
};
