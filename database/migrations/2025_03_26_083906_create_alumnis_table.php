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
        Schema::create('alumnis', function (Blueprint $table) {
            $table->id();
            $table->string('stid');
            $table->string('schid');
            $table->string('lname');
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('session_of_entry');
            $table->string('term_of_entry');
            $table->date('date_of_entry');
            $table->string('session_of_exit')->nullable();
            $table->string('term_of_exit')->nullable();
            $table->date('date_of_exit')->nullable();
            $table->text('reason_for_exit')->nullable();
            $table->timestamps();
            
            
            // For queries based on stid
            $table->index('stid');
            // For queries based on schid
            $table->index('schid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumnis');
    }
};
