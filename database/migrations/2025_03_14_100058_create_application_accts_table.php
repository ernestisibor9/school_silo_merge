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
        Schema::create('application_accts', function (Blueprint $table) {
            $table->id();
            $table->string('schid');
            $table->string('anum');
            $table->string('bnk');
            $table->string('aname');
            $table->string('app_acct')->default('application_fee');
            $table->timestamps();
            
            // For queries based on schid
            $table->index('schid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_accts');
    }
};
