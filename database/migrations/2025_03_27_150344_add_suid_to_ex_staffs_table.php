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
            $table->string('suid')->nullable()->after('schid'); // Add suid after sid
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ex_staffs', function (Blueprint $table) {
            //
            $table->dropColumn('suid');
        });
    }
};
