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
        Schema::create('clspay', function (Blueprint $table) {
            $table->id();
            $table->string('schid');
            $table->string('clsid');
            $table->string('amt');
            $table->string('phid');
            $table->string('sesid');
            $table->string('trmid');
            $table->timestamps();

            // For queries based on schid
            $table->index('schid');
            // For queries based on clsid
            $table->index('clsid');
            // For queries based on phid
            $table->index('phid');
            // For queries based on sesid
            $table->index('sesid');
            // For queries based on trmid
            $table->index('trmid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clspay');
    }
};
