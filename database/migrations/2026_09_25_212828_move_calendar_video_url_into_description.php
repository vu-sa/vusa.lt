<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The event page's separate "Video" field becomes a YouTube embed at the end of the
 * description — the same node the editor's Įterpti → YouTube produces. Query builder,
 * not the model, so the migration keeps working as Calendar changes. One-way by choice:
 * down() restores the column, not its values.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('calendar')
            ->whereNotNull('video_url')
            ->where('video_url', '!=', '')
            ->orderBy('id')
            ->each(function (object $event): void {
                $videoId = $this->videoId($event->video_url);

                if ($videoId === null) {
                    return;
                }

                DB::table('calendar')->where('id', $event->id)->update([
                    'description' => json_encode(
                        $this->appendEmbed($event->description, $videoId),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                    ),
                ]);
            });

        Schema::table('calendar', function (Blueprint $table) {
            $table->dropColumn('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('calendar', function (Blueprint $table) {
            $table->string('video_url')->nullable()->after('facebook_url');
        });
    }

    /** The field asked for a bare ID, but a pasted URL is accepted too. */
    private function videoId(string $value): ?string
    {
        $value = trim($value);

        if (preg_match('/^[\w-]{11}$/', $value)) {
            return $value;
        }

        if (preg_match('/(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:watch\?v=|embed\/|v\/|shorts\/))([\w-]{11})/', $value, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Appends to every language that has a description, or to Lithuanian when none does.
     *
     * @return array<string, string>
     */
    private function appendEmbed(?string $stored, string $videoId): array
    {
        $decoded = $stored === null ? null : json_decode($stored, true);
        $translations = is_array($decoded) ? $decoded : (filled($stored) ? ['lt' => $stored] : []);
        $translations = array_filter($translations, fn (mixed $html): bool => is_string($html) && trim(strip_tags($html, '<img><iframe>')) !== '');

        if ($translations === []) {
            $translations = ['lt' => ''];
        }

        $embed = sprintf(
            '<div data-youtube-video=""><iframe src="https://www.youtube-nocookie.com/embed/%s" width="640" height="480" allowfullscreen="true" title="YouTube"></iframe></div>',
            $videoId,
        );

        return array_map(fn (string $html): string => $html.$embed, $translations);
    }
};
