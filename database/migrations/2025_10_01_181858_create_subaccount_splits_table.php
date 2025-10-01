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
        Schema::create('subaccount_splits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schid');      // school id
            $table->unsignedBigInteger('clsid');      // class id
            $table->string('split_code')->unique();   // paystack split code
            $table->timestamps();

            // Optional indexes for performance
            $table->index('schid');
            $table->index('clsid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subaccount_splits');
    }
};
