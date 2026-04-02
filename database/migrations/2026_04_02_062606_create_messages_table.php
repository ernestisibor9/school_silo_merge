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
            $table->unsignedBigInteger('sender_id')->comment('ID of the sender');
            $table->char('sender_type', 1)->comment("'a' = domain admin, 's' = school admin");
            $table->unsignedBigInteger('receiver_id')->comment('ID of the receiver');
            $table->char('receiver_type', 2)->comment("'a' = domain admin, 's' = school admin, 'sf' = staff, 'st' = student");
            $table->string('subject')->comment('Message subject line');
            $table->text('message')->comment('The main message content');
            $table->string('attachment')->nullable()->comment('Optional file attachment path');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('For replies: references parent message');
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
