<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_episodes', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 9);
            $table->foreignId('source_assessment_id')->nullable()->unique()->constrained('assessments')->nullOnDelete();
            $table->char('status', 1)->default('A');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'status', 'started_at']);
        });

        Schema::create('episode_symptoms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_episode_id')->constrained()->cascadeOnDelete();
            $table->string('symptom_id', 10)->nullable();
            $table->string('custom_symptom_text', 200)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->char('status', 1)->default('A');
            $table->timestamp('first_observed_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->foreign('symptom_id')->references('symptom_id')->on('main_symptoms')->nullOnDelete();
            $table->unique(['health_episode_id', 'symptom_id']);
            $table->index(['health_episode_id', 'status']);
        });

        Schema::create('follow_up_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_symptom_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('severity');
            $table->decimal('temperature', 4, 1)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['episode_symptom_id', 'recorded_at']);
        });

        $legacyGroups = DB::table('symptom_follow_ups')
            ->join('assessments', 'assessments.id', '=', 'symptom_follow_ups.assessment_id')
            ->select('symptom_follow_ups.assessment_id', 'symptom_follow_ups.user_id', 'assessments.symptom_id')
            ->distinct()->get();

        foreach ($legacyGroups as $group) {
            $episodeId = DB::table('health_episodes')->insertGetId([
                'user_id' => $group->user_id,
                'source_assessment_id' => $group->assessment_id,
                'status' => 'A',
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $episodeSymptomId = DB::table('episode_symptoms')->insertGetId([
                'health_episode_id' => $episodeId,
                'symptom_id' => $group->symptom_id,
                'is_primary' => true,
                'status' => 'A',
                'first_observed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('symptom_follow_ups')->where('assessment_id', $group->assessment_id)
                ->orderBy('id')->each(function ($entry) use ($episodeSymptomId) {
                    DB::table('follow_up_entries')->insert([
                        'episode_symptom_id' => $episodeSymptomId,
                        'severity' => $entry->severity,
                        'temperature' => $entry->temperature,
                        'note' => $entry->note,
                        'recorded_at' => $entry->recorded_at,
                        'created_at' => $entry->created_at,
                        'updated_at' => $entry->updated_at,
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_entries');
        Schema::dropIfExists('episode_symptoms');
        Schema::dropIfExists('health_episodes');
    }
};
