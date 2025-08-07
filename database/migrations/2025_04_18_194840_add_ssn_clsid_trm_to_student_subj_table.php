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
        Schema::table('student_subj', function (Blueprint $table) {
            //
            $table->string('ssn')->nullable()->after('schid'); // Adjust 'after' as needed
            $table->string('clsid')->nullable();
            $table->string('trm')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_subj', function (Blueprint $table) {
            // 
             $table->dropColumn(['ssn', 'clsid', 'trm']);
        });
    }
};
