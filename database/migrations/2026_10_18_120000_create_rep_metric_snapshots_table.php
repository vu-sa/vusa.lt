<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Daily rep outcome metrics; one row per day, the reference row flagged as the baseline. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rep_metric_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->date('taken_on')->unique();
            $table->unsignedSmallInteger('window_days');
            $table->json('metrics');
            $table->boolean('is_baseline')->default(false)->index();
            $table->timestamps();
        });

        // The pre-redesign numbers, measured over the year before 2026-09-17, so the baseline survives the move out of code.
        DB::table('rep_metric_snapshots')->insert([
            'taken_on' => '2026-09-17',
            'window_days' => 365,
            'metrics' => json_encode(array_map(fn (float $value): array => ['value' => $value, 'part' => null, 'whole' => null], [
                'recorded_within_week' => 91.9,
                'vote_information' => 35.1,
                'active_reps' => 27.0,
                'task_completion' => 67.2,
                'periodicity_gap' => 44.9,
            ])),
            'is_baseline' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('rep_metric_snapshots');
    }
};
