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
        Schema::create('sch_cls', function (Blueprint $table) {
            $table->id();
            $table->string('schid');
            $table->string('cls_id');
            $table->string('name');
            $table->timestamps();

            // For queries based on schid
            $table->index('schid');
            // For queries based on cls_id
            $table->index('cls_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sch_cls');
    }
};
