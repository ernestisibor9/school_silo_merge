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
        Schema::create('student_psy', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->string('punc');
            $table->string('hon');
            $table->string('pol');
            $table->string('neat');
            $table->string('pers');
            $table->string('rel');
            $table->string('dil');
            $table->string('cre');
            $table->string('pat');
            $table->string('verb');
            $table->string('gam');
            $table->string('musc');
            $table->string('drw');
            $table->string('wrt');
            $table->string('stid');
            $table->string('schid');
            $table->string('ssn');
            $table->string('trm');
            $table->string('clsm');
            $table->string('clsa');
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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_psy');
    }
};
