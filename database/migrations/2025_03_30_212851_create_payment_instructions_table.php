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
        Schema::create('payment_instructions', function (Blueprint $table) {
            $table->id();
            $table->string('schid');
            $table->string('clsid');
            $table->string('sesid');
            $table->string('trmid');
            $table->text('payment_ins');
            $table->timestamps();

            // For queries based on schid
            $table->index('schid');
            // For queries based on clsid
            $table->index('clsid');
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
        Schema::dropIfExists('payment_instructions');
    }
};
