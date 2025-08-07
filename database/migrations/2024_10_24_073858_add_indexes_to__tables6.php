<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIndexesToTables6 extends Migration
{
    public function up()
    {
        Schema::table('student_academic_data', function (Blueprint $table) {
            $table->index('new_class');
            $table->index('new_class_main');
        });
    }

    public function down()
    {
        Schema::table('student_academic_data', function (Blueprint $table) {
            $table->dropIndex(['new_class']);
            $table->dropIndex(['new_class_main']);
        });
    }
}
