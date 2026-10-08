<?php

use App\Services\Media\LegacyImageBackfill;
use Illuminate\Database\Migrations\Migration;

/**
 * Only queues the copy (see LegacyImageBackfill); the imports run on the long-running worker
 * after the site is back online.
 *
 * Transitional(legacy-images): delete with LegacyImageBackfill, together with its test.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(LegacyImageBackfill::class)->dispatch();
    }

    public function down(): void {}
};
