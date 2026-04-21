<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('conversation_id')->index();

            $table->unsignedBigInteger('sender_id');
            $table->char('sender_type', 2); // a, s

            $table->text('message');
            $table->string('subject');
            $table->string('attachment')->nullable();

            $table->unsignedBigInteger('parent_id')->nullable(); // reply support

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
