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
        Schema::create('school_general_data', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->string('state');
            $table->string('lga');
            $table->text('addr');
            $table->text('vision');
            $table->text('mission');
            $table->text('values');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_general_data');
    }
};
