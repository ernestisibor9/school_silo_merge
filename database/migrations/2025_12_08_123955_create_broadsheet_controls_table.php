<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('broadsheet_controls', function (Blueprint $table) {
            $table->id();
            $table->string('sid');
            $table->string('schid');
            $table->string('ssn');
            $table->string('trm');
            $table->string('clsm');
            $table->string('clsa');
            $table->integer('stat')->default(1); // <-- add your block/unblock column
            $table->timestamps();

            // For queries based on schid
            $table->index('schid');
            // For queries based on sid
            $table->index('sid');
            // For queries based on ssn
            $table->index('ssn');
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
        Schema::dropIfExists('broadsheet_controls');
    }
};
