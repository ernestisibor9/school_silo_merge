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
        Schema::table('school_bill_payments', function (Blueprint $table) {
            $table->decimal('transaction_charge', 12, 2)
                ->default(0)
                ->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('school_bill_payments', function (Blueprint $table) {
            $table->dropColumn('transaction_charge');
        });
    }
};
