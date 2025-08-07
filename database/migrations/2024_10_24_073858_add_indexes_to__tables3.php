<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIndexesToTables3 extends Migration
{
    public function up()
    {
        // Add full-text index on 'name' column in the cls table
        DB::statement('ALTER TABLE subj ADD FULLTEXT INDEX subj_fulltext (name)');
    }

    public function down()
    {
        // Drop the full-text index from the 'name' column in the cls table
        DB::statement('ALTER TABLE subj DROP INDEX subj_fulltext');
    }
}
