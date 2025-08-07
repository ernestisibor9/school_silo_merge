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
        Schema::create('school_prop_data', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->string('pfname');
            $table->string('pmname')->nullable();
            $table->string('plname');
            $table->string('psex');
            $table->string('pphn');
            $table->text('paddr');
            $table->string('peml');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_prop_data');
    }
};
