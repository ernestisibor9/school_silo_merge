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
        Schema::create('arm_result_conf', function (Blueprint $table) {
            $table->id();
            $table->string('arm');
            $table->string('stid');
            $table->string('schid');
            $table->string('clsid');
            $table->string('sbid');
            $table->string('ssn');
            $table->string('trm');
            $table->text('rmk');
            $table->text('stat');
            $table->timestamps();

            // For queries based on arm
            $table->index('arm');
            // For queries based on stid
            $table->index('stid');
            // For queries based on schid
            $table->index('schid');
            // For queries based on clsid
            $table->index('clsid');
            // For queries based on sbid
            $table->index('sbid');
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
        Schema::dropIfExists('arm_result_conf');
    }
};
