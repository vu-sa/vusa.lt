<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Collapses the `block-link` navigation link type into `link`.
 *
 * The two differed only in weight and padding, which is what made a menu column carrying both
 * read as two mismatched groups. The design has exactly one link treatment, so after the redesign
 * they rendered identically and the distinction they used to carry — hover underline vs hover
 * fill — no longer exists.
 *
 * Irreversible on purpose: once collapsed there is nothing to tell the two apart, and guessing
 * which rows were which would be inventing data.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('navigation')
            ->where('extra_attributes', 'like', '%block-link%')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $attributes = json_decode((string) $row->extra_attributes, true);

                    if (! is_array($attributes) || ($attributes['type'] ?? null) !== 'block-link') {
                        continue;
                    }

                    $attributes['type'] = 'link';

                    DB::table('navigation')
                        ->where('id', $row->id)
                        ->update(['extra_attributes' => json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                }
            });
    }
};
