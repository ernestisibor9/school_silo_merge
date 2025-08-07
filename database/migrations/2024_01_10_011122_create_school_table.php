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
        Schema::create('school', function (Blueprint $table) {
            $table->string('sid')->primary();
            $table->string('name');
            $table->string('count');
            $table->string('s_web');
            $table->string('s_info');
            $table->string('sbd');
            $table->string('sch3');
            $table->string('cssn');
            $table->string('ctrm');
            $table->string('ctrmn');
            $table->timestamps();

            // For queries based on sbd
            $table->index('sbd');
        });
        // Add full-text index on lname & oname column
        DB::statement('ALTER TABLE school ADD FULLTEXT INDEX school_fulltext (name)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school');
    }
};
