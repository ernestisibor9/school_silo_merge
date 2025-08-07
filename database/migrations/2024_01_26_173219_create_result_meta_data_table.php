<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('result_meta', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->string('schid');
            $table->string('ssn');
            $table->string('trm');
            $table->string('ntrd');
            $table->string('sdob');
            $table->string('spos');
            $table->timestamps();

             $table->index('schid');
             $table->index('ssn');
             $table->index('trm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_meta');
    }
};
