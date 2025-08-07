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
        Schema::create('sch_staff_role', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role');
            $table->string('schid');
            $table->timestamps();
             // For queries based on schid
             $table->index('schid');
        });
        // Add full-text index on name column
        DB::statement('ALTER TABLE sch_staff_role ADD FULLTEXT INDEX sch_staff_role_fulltext (name)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sch_staff_role');
    }
};
