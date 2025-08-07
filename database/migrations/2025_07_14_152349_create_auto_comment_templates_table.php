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
        Schema::create('auto_comment_templates', function (Blueprint $table) {
            $table->id();
            $table->string('schid');
            $table->string('ssn');
            $table->string('trm');
            $table->string('clsm');
            $table->string('clsa');
            $table->string('role');
            $table->string('grade');
            $table->text('comment');
            $table->timestamps();
            
            // For queries based on schid
            $table->index('schid');
            // For queries based on ssn
            $table->index('ssn');
            // For queries based on trm
            $table->index('trm');
            // For queries based on clsm
            $table->index('clsm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_comment_templates');
    }
};
