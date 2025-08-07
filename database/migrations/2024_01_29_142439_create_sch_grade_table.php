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
        Schema::create('sch_grade', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->string('grd');
            $table->string('g0');
            $table->string('g1');
            $table->string('schid');
            $table->string('clsid');
            $table->string('ssn');
            $table->string('trm');
            $table->timestamps();

            // For queries based on schid
            $table->index('schid');
            // For queries based on clsid
            $table->index('clsid');
            // For queries based on ssn
            $table->index('ssn');
            // For queries based on trm
            $table->index('trm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sch_grade');
    }
};
