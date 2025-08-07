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
        Schema::create('msg', function (Blueprint $table) {
            $table->id();
            $table->text('body');
            $table->string('who');
            $table->string('tid');
            $table->string('art');
            $table->timestamps();

             // For queries based on tid
             $table->index('tid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('msg');
    }
};
