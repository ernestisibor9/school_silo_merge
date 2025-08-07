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
        Schema::create('partner_basic_data', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            $table->string('phn');
            $table->string('verif');
            $table->string('eml');
            $table->string('pcode');
            $table->timestamps();

            // For queries based on verif
            $table->index('verif');
            // For queries based on pcode
            $table->index('pcode');
        });
        // Add full-text index on school name column
        DB::statement('ALTER TABLE partner_basic_data ADD FULLTEXT INDEX partner_basic_data_fulltext (fname, lname, mname)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_basic_data');
    }
};
