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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('schid'); // School ID
            $table->string('ssn'); // Session
             $table->string('trm'); // Session
            $table->string('clsm'); // Class Main
            $table->string('clsa'); // Class Arm
            $table->string('sid');  // Student ID
            $table->string('stid')->nullable(); // Staff ID
            $table->unsignedTinyInteger('week')->comment('Week number from 1 to 14');
            $table->enum('day', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])->comment('Week days (Mon - Fri)');
            $table->tinyInteger('status')->default(0)->comment('0 = Draft, 1 = Present, 2 = Absent');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
