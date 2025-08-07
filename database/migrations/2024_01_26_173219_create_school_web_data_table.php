<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_web_data', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->text('sname');
            $table->string('color');
            $table->string('addr');
            $table->string('country');
            $table->string('state');
            $table->string('lga');
            $table->string('phn');
            $table->string('eml');
            $table->string('vision');
            $table->string('values');
            $table->string('year');
            $table->text('about');
            $table->string('motto');
            $table->string('fb');
            $table->string('isg');
            $table->string('yt');
            $table->string('wh');
            $table->string('lkd');
            $table->string('tw');
            $table->timestamps();
        });
        // Add full-text index on school name column
        DB::statement('ALTER TABLE school_web_data ADD FULLTEXT INDEX school_web_data_fulltext (sname)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_web_data');
    }
};
