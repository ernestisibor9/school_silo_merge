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
        Schema::create('msgthread', function (Blueprint $table) {
            $table->id();
            $table->string('from');
            $table->string('from_uid');
            $table->string('to');
            $table->string('to_uid');
            $table->string('last_msg');
            $table->string('subject');
            $table->string('from_mail');
            $table->string('to_mail');
            $table->timestamps();

             // For queries based on from_uid
             $table->index('from_uid');
             // For queries based on to_uid
             $table->index('to_uid');
             // For queries based on updated_at
             $table->index('updated_at');
        });
         // Add full-text index on subject column
         DB::statement('ALTER TABLE msgthread ADD FULLTEXT INDEX msgthread_fulltext (subject)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('msgthread');
    }
};
