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
        Schema::create('lesson_plan_options', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | SCHOOL / CLASS INFORMATION
            |--------------------------------------------------------------------------
            */

            $table->string('schid');
            $table->string('clsm');

            $table->string('ssn');
            $table->string('trm');
            $table->string('sbj');

            /*
            |--------------------------------------------------------------------------
            | PLAN TYPE
            |--------------------------------------------------------------------------
            */

            $table->enum('plan_type', [
                'weekly',
                'termly',
            ]);

            $table->string('weekly')->nullable();

            /*
            |--------------------------------------------------------------------------
            | LESSON INFORMATION
            |--------------------------------------------------------------------------
            */

            $table->date('date');

            $table->time('time_from');
            $table->time('time_to');

            $table->string('period');
            $table->string('duration');

            $table->string('sex')->nullable();

            $table->string('topic');

            /*
            |--------------------------------------------------------------------------
            | REPEATABLE LESSON INFORMATION
            |--------------------------------------------------------------------------
            */

            $table->json('sub_topic')->nullable();

            $table->json('lesson_objectives')->nullable();

            $table->json('instructional_sources_material')->nullable();

            /*
            |--------------------------------------------------------------------------
            | STEP I
            | Identification of Previous Ideas / Previous Knowledge
            |--------------------------------------------------------------------------
            */

            $table->json('step1_previous_knowledge')->nullable();

            $table->string('step1_mode')->nullable();

            $table->json('step1_teacher_activities')->nullable();

            $table->json('step1_student_activities')->nullable();

            /*
            |--------------------------------------------------------------------------
            | STEP II
            | Exploration
            | Demonstration / Participation / Investigatory
            |--------------------------------------------------------------------------
            */

            $table->string('step2_mode')->nullable();

            $table->json('step2_teacher_activities')->nullable();

            $table->json('step2_student_activities')->nullable();

            /*
            |--------------------------------------------------------------------------
            | STEP III
            | Discussion
            |--------------------------------------------------------------------------
            */

            $table->string('step3_mode')->nullable();

            $table->json('step3_teacher_activities')->nullable();

            $table->json('step3_student_activities')->nullable();

            /*
            |--------------------------------------------------------------------------
            | STEP IV
            | Application
            |--------------------------------------------------------------------------
            */

            $table->string('step4_mode')->nullable();

            $table->json('step4_teacher_activities')->nullable();

            $table->json('step4_student_activities')->nullable();

            /*
            |--------------------------------------------------------------------------
            | STEP V
            | Evaluation
            |--------------------------------------------------------------------------
            */

            $table->string('step5_mode')->nullable();

            $table->json('step5_teacher_activities')->nullable();

            $table->json('step5_student_activities')->nullable();

            /*
            |--------------------------------------------------------------------------
            | CONCLUSION OF LESSON
            |--------------------------------------------------------------------------
            */

            $table->json('summary')->nullable();

            $table->json('conclusion')->nullable();

            $table->json('assignment')->nullable();

            $table->json('reference')->nullable();

            /*
            |--------------------------------------------------------------------------
            | SUBJECT HEAD USE ONLY
            |--------------------------------------------------------------------------
            */

            $table->enum('topic_tally', [
                'yes',
                'no',
            ])->nullable();

            $table->text('other_comments')->nullable();

            $table->string('subject_head_signature')->nullable();

            $table->date('subject_head_signature_date')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | INDEXES
            |--------------------------------------------------------------------------
            */

            $table->index('schid');
            $table->index('clsm');
            $table->index('ssn');
            $table->index('trm');
            $table->index('sbj');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_options');
    }
};
