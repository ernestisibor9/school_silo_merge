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
        Schema::table('ex_staffs', function (Blueprint $table) {
            //
            $table->string('sch3')->after('mname')->nullable();  
            $table->integer('count')->after('mname')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ex_staffs', function (Blueprint $table) {
            //
             $table->dropColumn(['sch3', 'count']);
        });
    }
};
