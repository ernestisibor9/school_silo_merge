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
        Schema::create('pay', function (Blueprint $table) {
            $table->id();
            $table->string('sid');
            $table->string('rid');
            $table->string('rname');
            $table->integer('amt');
            $table->bigInteger('time');
            $table->string('ref');
            $table->string('typ');
            $table->string('pid');
            $table->timestamps();

            // For queries based on sid
            $table->index('sid');
            // For queries based on rid
            $table->index('rid');
            // For queries based on typ
            $table->index('typ');
            // Index on 'amt' to make summing faster
            $table->index('amt');
            // Index on 'time'
            $table->index('time');
            // For queries based on pid
            $table->index('pid');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay');
    }
};
