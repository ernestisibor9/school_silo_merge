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
        Schema::create('in_expenditure', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('purp');
            $table->integer('amt');
            $table->string('mode');
            $table->string('schid');
            $table->string('ssn');
            $table->string('trm');
            $table->bigInteger('time');
            $table->string('ext')->nullable();
            $table->timestamps();

            // For queries based on trm
            $table->index('trm');
            // For queries based on ssn
            $table->index('ssn');
            // For queries based on time
            $table->index('time');
            // For queries based on schid
            $table->index('schid');
            // For queries based on mode
            $table->index('mode');
            // For queries based on amt
            $table->index('amt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('in_expenditure');
    }
};
