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
        Schema::create('school_subj', function (Blueprint $table) {
            $table->id();
            $table->string('subj_id');
            $table->string('schid');
            $table->string('name');
            $table->string('comp');
            $table->timestamps();

            // For queries based on schid
            $table->index('schid');
            // For queries based on subj_id
            $table->index('subj_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_subj');
    }
};
