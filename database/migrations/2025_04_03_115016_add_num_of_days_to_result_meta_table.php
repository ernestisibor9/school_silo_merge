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
        Schema::table('result_meta', function (Blueprint $table) {
            // 
            $table->string('num_of_days')->nullable()->after('spos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('result_meta', function (Blueprint $table) {
            //
             $table->dropColumn('num_of_days'); 
        });
    }
};
