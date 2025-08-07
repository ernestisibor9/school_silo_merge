<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIndexesToTables2 extends Migration
{
    public function up()
    {
        // Add full-text index on 'name' column in the cls table
        DB::statement('ALTER TABLE cls ADD FULLTEXT INDEX cls_fulltext (name)');
    }

    public function down()
    {
        // Drop the full-text index from the 'name' column in the cls table
        DB::statement('ALTER TABLE cls DROP INDEX cls_fulltext');
    }
}
