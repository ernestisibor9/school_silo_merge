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
        Schema::create('old_student', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->string('sid');
            $table->string('schid');
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            $table->string('suid');
            $table->string('ssn');
            $table->string('clsm');
            $table->string('clsa');
            $table->string('more')->nullable();
            $table->timestamps();

            // For queries based on schid
            $table->index('schid');
            // For queries based on ssn
            $table->index('ssn');
            // For queries based on clsm
            $table->index('clsm');
            // For queries based on clsa
            $table->index('clsa');
        });
        // Add full-text index on lname & fname column
        DB::statement('ALTER TABLE old_student ADD FULLTEXT INDEX old_student_fulltext (fname, lname)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('old_student');
    }
};
