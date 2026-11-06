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
        Schema::create('technology_fees', function (Blueprint $table) {
            $table->id();

            $table->string('academic_session', 50);
            $table->string('term', 50);

            $table->decimal('fee_per_student', 12, 2);

            $table->enum('status', ['draft', 'published'])
                  ->default('draft');

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['academic_session', 'term'],
                'technology_fees_session_term_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technology_fees');
    }
};
