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
        Schema::create('subject_dests', function (Blueprint $table) {
            $table->id();
            $table->string('uid');
            $table->string('schid');
            $table->string('stid');
            $table->string('sbj');
            $table->string('sesn');
            $table->string('trm');
            $table->timestamps();

            // For queries based on schid
            $table->index('schid');
              // For queries based on sbj
            $table->index('sbj');
            // For queries based on ssn
            $table->index('sesn');
            // For queries based on trm
            $table->index('trm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_dests');
    }
};
