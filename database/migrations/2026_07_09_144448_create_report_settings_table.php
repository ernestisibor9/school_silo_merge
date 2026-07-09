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
        Schema::create('report_settings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('schid');
            $table->unsignedBigInteger('ssn');
            $table->unsignedBigInteger('clsid');

            // 1 = Show Position, 0 = Hide Position
            $table->boolean('show_position')->default(true);

            $table->timestamps();

            // Prevent duplicate settings for the same school/session/class
            $table->unique(['schid', 'ssn', 'clsid']);

            // Optional indexes for faster lookups
            $table->index('schid');
            $table->index('ssn');
            $table->index('clsid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_settings');
    }
};
