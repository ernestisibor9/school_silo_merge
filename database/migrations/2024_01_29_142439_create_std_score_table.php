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
        Schema::create('std_score', function (Blueprint $table) {
            $table->id();
            $table->string('stid');
            $table->integer('scr');
            $table->string('sbj');
            $table->string('schid');
            $table->string('clsid');
            $table->string('ssn');
            $table->string('trm');
            $table->string('aid');
            $table->timestamps();

            // For queries based on stid
            $table->index('stid');
            // For queries based on schid
            $table->index('schid');
            // For queries based on clsid
            $table->index('clsid');
            // For queries based on ssn
            $table->index('ssn');
            // For queries based on trm
            $table->index('trm');
            // For queries based on scr
            $table->index('scr');
            // For queries based on sbj
            $table->index('sbj');
            // For queries based on aid
            $table->index('aid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('std_score');
    }
};
