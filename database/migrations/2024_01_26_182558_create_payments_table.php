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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('schid');
            $table->string('stid');
            $table->string('ssnid');
            $table->string('trmid');
            $table->string('clsid');
            $table->string('name');
            $table->string('exp');
            $table->string('amt');
            $table->string('lid');
            $table->timestamps();


            // Indexes for faster queries
            $table->index('schid');
            $table->index('stid');
            $table->index('ssnid');
            $table->index('trmid');
            $table->index('clsid');
            $table->index('lid');
            $table->index('amt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['payhead_id']);
            $table->dropColumn('payhead_id');
        });

        Schema::dropIfExists('payments');
    }
};
