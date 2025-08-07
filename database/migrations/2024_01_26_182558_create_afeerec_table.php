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
        Schema::create('afeerec', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->string('stid');
            $table->string('schid');
            $table->string('clsid');
            $table->integer('amt');
            $table->timestamps();

            // For queries based on stid
            $table->index('stid');
            // For queries based on schid
            $table->index('schid');
            // For queries based on clsid
            $table->index('clsid');
            // For queries based on amt
            $table->index('amt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('afeerec');
    }
};
