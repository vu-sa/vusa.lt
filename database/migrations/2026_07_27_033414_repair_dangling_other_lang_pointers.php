<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Clears `other_lang_id` on live pages and news whose counterpart is deleted or gone.
 *
 * Nothing ever released the pointer when the other language version was soft-deleted,
 * so these records advertise a language switch that resolves to nothing — the switcher
 * silently disappears on the public page. Going forward the `deleting` hook on Page and
 * News prevents this; the migration repairs the rows that predate it.
 *
 * Deliberately left alone: the `other_lang_id` of *deleted* records. They keep it so a
 * restore can re-establish the pairing, and PairTranslatedRecord now reclaims the value
 * from them on demand instead of colliding with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['pages', 'news'] as $table) {
            DB::table($table)
                ->whereNull('deleted_at')
                ->whereNotNull('other_lang_id')
                ->whereNotExists(function ($query) use ($table) {
                    $query->select(DB::raw(1))
                        ->from($table, 'counterpart')
                        ->whereColumn('counterpart.id', $table.'.other_lang_id')
                        ->whereNull('counterpart.deleted_at');
                })
                ->update(['other_lang_id' => null]);
        }
    }

    /**
     * Not reversible: the pointers removed here referenced records that are deleted or
     * no longer exist, so there is nothing coherent to restore them to.
     */
    public function down(): void {}
};
