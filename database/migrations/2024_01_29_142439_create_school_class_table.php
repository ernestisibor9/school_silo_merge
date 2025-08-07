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
        Schema::create('school_class', function (Blueprint $table) {
            $table->id();
            $table->string('schid');
            $table->string('clsid');
            $table->string('name');
            $table->timestamps();

            // For queries based on schid
            $table->index('schid');
            // For queries based on clsid
            $table->index('clsid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_class');
    }
};
