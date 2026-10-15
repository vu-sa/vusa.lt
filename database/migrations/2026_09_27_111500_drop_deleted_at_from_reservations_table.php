<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reservations do not use soft deletes; records are deleted immediately and permanently.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Permanently remove any legacy soft-deleted reservations and clean up their attachments.
        $deletedReservations = DB::table('reservations')->whereNotNull('deleted_at')->pluck('id');

        if ($deletedReservations->isNotEmpty()) {
            DB::table('reservation_resource')->whereIn('reservation_id', $deletedReservations)->delete();
            DB::table('reservation_user')->whereIn('reservation_id', $deletedReservations)->delete();
            DB::table('comments')
                ->where('commentable_type', 'reservation')
                ->whereIn('commentable_id', $deletedReservations)
                ->delete();
            DB::table('reservations')->whereIn('id', $deletedReservations)->delete();
        }

        // Clean up any soft-deleted reservation_resource rows if any exist
        if (Schema::hasColumn('reservation_resource', 'deleted_at')) {
            DB::table('reservation_resource')->whereNotNull('deleted_at')->delete();
        }

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        if (Schema::hasColumn('reservation_resource', 'deleted_at')) {
            Schema::table('reservation_resource', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->softDeletes();
        });

        if (Schema::hasTable('reservation_resource') && ! Schema::hasColumn('reservation_resource', 'deleted_at')) {
            Schema::table('reservation_resource', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }
};
