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
        Schema::create('silo_user', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->string('eml');
            $table->string('lname');
            $table->string('oname');
            $table->string('zone');
            $table->string('dob');
            $table->string('state');
            $table->string('lga');
            $table->string('phn');
            $table->string('role');
            $table->string('addr');
            $table->string('prev');
            $table->string('date');
            $table->integer('level');
            $table->string('verif');

            $table->timestamps();

            // For queries based on role
            $table->index('role');
            // For queries based on level
            $table->index('level');
            // For queries based on verif
            $table->index('verif');
            // For queries based on created_at
            $table->index('created_at');
        });
        // Add full-text index on lname & oname column
        DB::statement('ALTER TABLE silo_user ADD FULLTEXT INDEX dqau_fulltext (lname,oname)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('silo_user');
    }
};
