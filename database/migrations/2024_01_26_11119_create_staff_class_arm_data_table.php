<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('staff_class_arm', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->string('stid');
            $table->string('cls');
            $table->string('arm');
            $table->string('schid');
            $table->timestamps();

            // For queries based on stid
            $table->index('stid');
            // For queries based on cls
            $table->index('cls');
            // For queries based on arm
            $table->index('arm');
            // For queries based on schid
            $table->index('schid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_class_arm');
    }
};
