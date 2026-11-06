<?php

use App\Models\ContentPart;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        ContentPart::query()->chunkById(200, function (Collection $parts): void {
            foreach ($parts as $part) {
                $json = $part->getAttribute('json_content');
                if (! $json instanceof ArrayObject) {
                    continue;
                }

                $content = $json->toArray();
                $sanitized = $part->sanitizeJsonContentHtml($content);

                if ($sanitized === $content) {
                    continue;
                }

                $part->clearHtmlCache();
                DB::table('content_parts')->where('id', $part->id)->update([
                    'json_content' => json_encode($sanitized, JSON_THROW_ON_ERROR),
                    'updated_at' => DB::raw('updated_at'),
                ]);
            }
        });

        Cache::tags(['pages', 'homepage'])->flush();
    }

    public function down(): void
    {
        // Removed markup cannot be restored.
    }
};
