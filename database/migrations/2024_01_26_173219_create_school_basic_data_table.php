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
        Schema::create('school_basic_data', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->text('sname');
            $table->string('phn');
            $table->string('eml');
            $table->string('pcode');
            $table->string('pay');
            $table->timestamps();
            
            // For queries based on pay
            $table->index('pay');
            // For queries based on pcode
            $table->index('pcode');
        });
        // Add full-text index on school name column
        DB::statement('ALTER TABLE school_basic_data ADD FULLTEXT INDEX schbi_fulltext (sname)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_basic_data');
    }
};
