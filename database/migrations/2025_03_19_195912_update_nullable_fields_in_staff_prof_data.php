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
        Schema::table('staff_prof_data', function (Blueprint $table) {
            //
            $table->string('grad_date')->nullable()->change();
            $table->string('univ')->nullable()->change();
            $table->string('qual')->nullable()->change();
            $table->string('trcn')->nullable()->change();
            $table->string('hqual')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_prof_data', function (Blueprint $table) {
            //
            $table->string('grad_date')->nullable(false)->change();
            $table->string('univ')->nullable(false)->change();
            $table->string('qual')->nullable(false)->change();
            $table->string('trcn')->nullable(false)->change();
            $table->string('hqual')->nullable(false)->change();
        });
    }
};
