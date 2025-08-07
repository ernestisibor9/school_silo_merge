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
        Schema::table('alumnis', function (Blueprint $table) {
            //
            $table->string('exit_class')->nullable()->after('date_of_exit'); 
            $table->string('exit_class_arm')->nullable()->after('exit_class'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumnis', function (Blueprint $table) {
            //  
             $table->dropColumn(['exit_class', 'exit_class_arm']);
        });
    }
};
