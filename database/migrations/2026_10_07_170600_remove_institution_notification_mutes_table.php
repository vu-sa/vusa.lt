<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('institution_notification_mutes')) {
            return;
        }

        // Preserve the choice not to receive optional institution notifications.
        DB::table('institution_follows')
            ->whereExists(function (Builder $query): void {
                $query->select('id')
                    ->from('institution_notification_mutes')
                    ->whereColumn('institution_notification_mutes.user_id', 'institution_follows.user_id')
                    ->whereColumn('institution_notification_mutes.institution_id', 'institution_follows.institution_id');
            })
            ->delete();

        Schema::drop('institution_notification_mutes');
    }

    public function down(): void
    {
        // The schema can be restored, but deleted mute preferences and follows cannot.
        Schema::create('institution_notification_mutes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('institution_id')->constrained()->cascadeOnDelete();
            $table->timestamp('muted_at');
            $table->timestamps();

            $table->unique(['user_id', 'institution_id']);
        });
    }
};
