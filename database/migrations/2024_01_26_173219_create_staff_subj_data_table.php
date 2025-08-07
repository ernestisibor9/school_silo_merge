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
        Schema::create('staff_subj', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->string('stid');
            $table->string('sbj');
            $table->string('schid');
            $table->timestamps();

            // For queries based on stid
            $table->index('stid');
            // For queries based on sbj
            $table->index('sbj');
            // For queries based on schid
            $table->index('schid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_subj');
    }
};
