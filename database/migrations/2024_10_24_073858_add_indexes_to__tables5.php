<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIndexesToTables5 extends Migration
{
    public function up()
    {
        Schema::table('staff_subj', function (Blueprint $table) {
            $table->index('stid');
            $table->index('sbj');
            $table->index('schid');
        });
    }

    public function down()
    {
        Schema::table('staff_subj', function (Blueprint $table) {
            $table->dropIndex(['stid']);
            $table->dropIndex(['sbj']);
            $table->dropIndex(['schid']);
        });
    }
}
