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
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_note_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->json('references')->nullable();
            $table->json('content')->nullable();
            $table->json('general_evaluation')->nullable();
            $table->json('weekend_assignment')->nullable();
            $table->json('theory')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
