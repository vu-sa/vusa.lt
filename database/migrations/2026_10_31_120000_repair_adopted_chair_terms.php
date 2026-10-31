<?php

use App\Actions\Dutiables\LinkDutiableToExOfficioSource;
use App\Actions\Dutiables\ReconcileExOfficioSeats;
use App\Models\Pivots\Dutiable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A reversed Dekanato → Pirmininkas rule let the old backfill adopt two chairs' own open-ended terms,
 * overwriting their dates, and removing it ended them. Restores the chairs and follows the corrected rule.
 */
return new class extends Migration
{
    /** @var list<array{chair: string, deanery: string}> */
    private const array REPAIRS = [
        ['chair' => '01ktm6vay1k6fy64hzxkvh5kv5', 'deanery' => '01m574d988aq4nx01mmfw8h358'],
        ['chair' => '01ktm6rq8e7v2ywtbtqsefy0c3', 'deanery' => '01m574wzegyjzkyftyy0yd2h30'],
    ];

    /** The chairs' seats were never rewritten, so they still hold the original term's start. */
    private const string CHAIR_START = '2026-06-01';

    public function up(): void
    {
        foreach (self::REPAIRS as $repair) {
            DB::transaction(function () use ($repair): void {
                $chair = Dutiable::query()->lockForUpdate()->find($repair['chair']);
                $deanery = Dutiable::query()->lockForUpdate()->find($repair['deanery']);

                if ($chair === null || $deanery === null || $chair->via_dutiable_id !== $deanery->id) {
                    return;
                }

                ReconcileExOfficioSeats::paused(fn () => $chair->update([
                    'via_dutiable_id' => null,
                    'assignment_origin' => 'manual',
                    'start_date' => self::CHAIR_START,
                    'end_date' => null,
                ]));

                if ($chair->duty->exOfficioTargetDuties()->whereKey($deanery->duty_id)->exists()) {
                    LinkDutiableToExOfficioSource::execute($deanery, $chair);
                }

                ReconcileExOfficioSeats::forSource($chair);
            });
        }
    }

    public function down(): void {}
};
