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
        Schema::create('student', function (Blueprint $table) {
            $table->string('sid')->primary();
            $table->string('schid');
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            $table->string('count');
            $table->string('year');
            $table->string('term');
            $table->string('sch3');
            $table->string('s_basic');
            $table->string('s_medical');
            $table->string('s_parent');
            $table->string('s_academic');
            $table->string('stat');
            $table->string('rfee');
            $table->string('cuid')->nullable();
            $table->timestamps();

            // For queries based on schid
            $table->index('schid');
            // For queries based on count
            $table->index('count');
            // For queries based on year
            $table->index('year');
            // For queries based on term
            $table->index('term');
            // For queries based on sch3
            $table->index('sch3');
            // For queries based on stat
            $table->index('stat');
            // For queries based on cuid
            $table->index('cuid');
            // For queries based on rfee
            $table->index('rfee');
        });
        // Add full-text index on lname & oname column
        DB::statement('ALTER TABLE student ADD FULLTEXT INDEX student_fulltext (fname, lname)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student');
    }
};
