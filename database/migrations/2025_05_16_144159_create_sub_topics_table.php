<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sub_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('sub_topic_content_one')->nullable();
            $table->text('sub_topic_content_two')->nullable();
            $table->text('sub_topic_content_three')->nullable();
            $table->text('sub_topic_content_four')->nullable();
            $table->text('sub_topic_content_five')->nullable();
            $table->text('sub_topic_content_six')->nullable();
            $table->text('sub_topic_content_seven')->nullable();
            $table->text('sub_topic_content_eight')->nullable();
            $table->text('sub_topic_content_nine')->nullable();
            $table->text('sub_topic_content_ten')->nullable();
            $table->json('sub_topic_evaluation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_topics');
    }
};
