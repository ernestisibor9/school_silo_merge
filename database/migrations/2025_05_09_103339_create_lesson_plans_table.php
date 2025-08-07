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
        Schema::create('lesson_plans', function (Blueprint $table) {
        $table->id();
        $table->string('schid');
        $table->string('clsm');
        $table->string('ssn');
        $table->string('trm');
        $table->string('sbj');
        $table->string('topic');
        $table->json('sub_topic')->nullable();           // array
        $table->date('date');
        $table->integer('no_of_class');
        $table->string('average_age');
        $table->time('time_from');
        $table->time('time_to');
        $table->string('duration');
        $table->json('learning_materials')->nullable();  // array
        $table->text('lesson_objectives');
        $table->timestamps();
        
        // For queries based on schid
        $table->index('schid');
        // For queries based on ssn
        $table->index('ssn');
        // For queries based on trm
        $table->index('trm');
        // For queries based on clsm
        $table->index('clsm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_plans');
    }
};
