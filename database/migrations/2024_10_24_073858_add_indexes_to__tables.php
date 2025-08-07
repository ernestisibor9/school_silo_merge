<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToTables extends Migration
{
    public function up()
    {
        // Add index to the 'rfee' column in the student table
        Schema::table('student', function (Blueprint $table) {
            $table->index('rfee');
        });

        // Add index to the 'amt' column in the payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->index('amt');
        });
    }

    public function down()
    {
        // Drop the indexes if needed during rollback
        Schema::table('student', function (Blueprint $table) {
            $table->dropIndex(['rfee']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['amt']);
        });
    }
}
