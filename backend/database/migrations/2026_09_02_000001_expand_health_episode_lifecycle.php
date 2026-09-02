<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('health_episodes', 'paused_at')) {
            Schema::table('health_episodes', function (Blueprint $table) {
                $table->timestamp('paused_at')->nullable()->after('started_at');
                $table->string('end_reason', 30)->nullable()->after('ended_at');
                $table->text('end_note')->nullable()->after('end_reason');
            });
        }

        if (! Schema::hasTable('health_episode_assessments')) {
            Schema::create('health_episode_assessments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('health_episode_id')->constrained()->cascadeOnDelete();
                $table->foreignId('assessment_id')->unique('hea_assessment_unique')->constrained()->cascadeOnDelete();
                $table->string('relationship_type', 20)->default('related');
                $table->timestamp('attached_at');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('daily_health_record_health_episode')) {
            Schema::create('daily_health_record_health_episode', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('daily_health_record_id');
                $table->unsignedBigInteger('health_episode_id');
                $table->timestamps();
                $table->unique(['daily_health_record_id', 'health_episode_id'], 'daily_record_episode_unique');
                $table->foreign('daily_health_record_id', 'dhr_episode_record_fk')->references('id')->on('daily_health_records')->cascadeOnDelete();
                $table->foreign('health_episode_id', 'dhr_episode_episode_fk')->references('id')->on('health_episodes')->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('daily_health_records', 'recorded_at')) {
            Schema::table('daily_health_records', function (Blueprint $table) {
                $table->timestamp('recorded_at')->nullable()->after('recorded_on');
                $table->index(['user_id', 'recorded_at'], 'daily_records_user_recorded_at_idx');
            });
        }

        DB::table('health_episodes')->whereNotNull('source_assessment_id')->orderBy('id')->each(function ($episode): void {
            DB::table('health_episode_assessments')->updateOrInsert(
                ['assessment_id' => $episode->source_assessment_id],
                [
                    'health_episode_id' => $episode->id,
                    'relationship_type' => 'initial',
                    'attached_at' => $episode->started_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        });

        DB::table('daily_health_records')->whereNull('recorded_at')->orderBy('id')->each(function ($record): void {
            DB::table('daily_health_records')->where('id', $record->id)->update([
                'recorded_at' => $record->created_at ?? $record->recorded_on,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('daily_health_records', function (Blueprint $table) {
            $table->dropIndex('daily_records_user_recorded_at_idx');
            $table->dropColumn('recorded_at');
        });
        Schema::dropIfExists('daily_health_record_health_episode');
        Schema::dropIfExists('health_episode_assessments');
        Schema::table('health_episodes', function (Blueprint $table) {
            $table->dropColumn(['paused_at', 'end_reason', 'end_note']);
        });
    }
};
