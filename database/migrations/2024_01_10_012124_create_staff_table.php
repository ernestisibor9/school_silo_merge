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
        Schema::create('staff', function (Blueprint $table) {
            $table->string('sid')->primary();
            $table->string('schid');
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            $table->string('count');
            $table->string('sch3');
            $table->string('stat');
            $table->string('cuid')->nullable();
            $table->string('role');
            $table->string('role2');
            $table->string('s_basic');
            $table->string('s_prof');
            $table->timestamps();

            // For queries based on schid
            $table->index('schid');
            // For queries based on count
            $table->index('count');
            // For queries based on stat
            $table->index('stat');
            // For queries based on cuid
            $table->index('cuid');
            // For queries based on sch3
            $table->index('sch3');
            // For queries based on role
            $table->index('role');
            // For queries based on role2
            $table->index('role2');
        });
        // Add full-text index on lname & oname column
        DB::statement('ALTER TABLE staff ADD FULLTEXT INDEX staff_fulltext (fname, lname)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
