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
        Schema::create('ext_expenditure', function (Blueprint $table) {
            $table->id();
            $table->string('vendor');
            $table->string('item');
            $table->string('pv');
            $table->text('dets');
            $table->string('name');
            $table->string('phn');
            $table->integer('unit');
            $table->integer('qty');
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
            // For queries based on vendor
            $table->index('vendor');
            // For queries based on schid
            $table->index('schid');
            // For queries based on item
            $table->index('item');
            // For queries based on pv
            $table->index('pv');
            // For queries based on mode
            $table->index('mode');
            
            // For queries based on unit
            $table->index('unit');
            // For queries based on qty
            $table->index('qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_expenditure');
    }
};
