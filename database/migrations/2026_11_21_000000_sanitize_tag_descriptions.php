<?php

use App\Services\HtmlSanitizerService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Tag::description is now sanitized on write; rows saved before that are cleaned once here. */
return new class extends Migration
{
    public function up(): void
    {
        $sanitizer = app(HtmlSanitizerService::class);

        DB::table('tags')->whereNotNull('description')->chunkById(200, function ($tags) use ($sanitizer): void {
            foreach ($tags as $tag) {
                $translations = json_decode($tag->description, true);

                if (! is_array($translations)) {
                    continue;
                }

                $sanitized = array_map(
                    fn ($value) => is_string($value) ? $sanitizer->sanitizeRichContent($value) : $value,
                    $translations,
                );

                if ($sanitized === $translations) {
                    continue;
                }

                DB::table('tags')->where('id', $tag->id)->update([
                    'description' => json_encode($sanitized, JSON_THROW_ON_ERROR),
                    'updated_at' => DB::raw('updated_at'),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Removed markup cannot be restored.
    }
};
