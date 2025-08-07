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
        Schema::create('vendor', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phn');
            $table->text('addr');
            $table->string('bnk');
            $table->string('anum');
            $table->string('aname');
            $table->string('goods');
            $table->string('schid');
            $table->timestamps();

            // For queries based on schid
            $table->index('schid');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor');
    }
};
