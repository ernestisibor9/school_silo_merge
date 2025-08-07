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
        Schema::create('application_sub_accts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('acct_id'); // Foreign key to accts table
            $table->string('schid');
            $table->string('subaccount_code'); // Paystack Subaccount Code
            $table->decimal('percentage_charge', 5, 2)->default(0);
            $table->timestamps();
            
            $table->foreign('acct_id')->references('id')->on('application_accts')->onDelete('cascade');
            
            // For queries based on schid
            $table->index('schid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_sub_accts');
    }
};
