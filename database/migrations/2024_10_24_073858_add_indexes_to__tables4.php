<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIndexesToTables4 extends Migration
{
    public function up()
    {
        Schema::table('staff_class', function (Blueprint $table) {
            $table->index('stid');
            $table->index('cls');
            $table->index('schid');
        });
    }

    public function down()
    {
        Schema::table('staff_class', function (Blueprint $table) {
            $table->dropIndex(['stid']);
            $table->dropIndex(['cls']);
            $table->dropIndex(['schid']);
        });
    }
}
