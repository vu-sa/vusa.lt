<?php

namespace App\Models;

use App\Models\Pivots\AgendaItem;
use App\Models\Traits\LogsModelActivity;
use App\Services\HtmlSanitizerService;
use Database\Factories\AgendaItemNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Private, real-time collaborative notes ("Atstovų pastabos") for an agenda item.
 *
 * @property string $id
 * @property string $agenda_item_id
 * @property string|null $yjs_state
 * @property string|null $notes_html
 * @property string|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read AgendaItem $agendaItem
 * @property-read User|null $editor
 *
 * @method static \Database\Factories\AgendaItemNoteFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgendaItemNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgendaItemNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgendaItemNote query()
 *
 * @mixin \Eloquent
 */
#[Hidden(['yjs_state'])]
#[Unguarded]
class AgendaItemNote extends Model
{
    /** @use HasFactory<AgendaItemNoteFactory> */
    use HasFactory, HasUlids, LogsModelActivity;

    /**
     * The rendered snapshot is authored by any representative on the meeting and
     * re-served to all the others through `v-html`, so it is sanitized on the way
     * in. The CRDT state in `yjs_state` remains the source of truth — this column
     * only ever feeds display.
     */
    protected function notesHtml(): Attribute
    {
        return Attribute::make(
            set: function (?string $value): ?string {
                if ($value === null) {
                    return null;
                }

                $sanitized = app(HtmlSanitizerService::class)->sanitizeRichContent($value);

                return static::isBlankHtml($sanitized) ? null : $sanitized;
            },
        );
    }

    /**
     * Whether the markup carries nothing a reader would call a note.
     *
     * Opening an agenda item mounts the collaborative editor, which autosaves its
     * empty document (`<p></p>`) before anyone types. Storing that as content made
     * every item look annotated, so blank markup is normalised to null instead.
     */
    protected static function isBlankHtml(string $html): bool
    {
        if (preg_match('/<(?:img|iframe|table|hr|video|audio|embed)\b/i', $html) === 1) {
            return false;
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);

        // The byte pair \xC2\xA0 is a UTF-8 non-breaking space, which trim() keeps by default.
        return trim($text, " \t\n\r\0\x0B\xC2\xA0") === '';
    }

    /**
     * yjs_state is the binary CRDT document state — huge and unreadable in a
     * change log. notes_html is the human-facing rendered snapshot instead.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return $this->defaultActivitylogOptions()->logExcept(['yjs_state']);
    }

    public function agendaItem(): BelongsTo
    {
        return $this->belongsTo(AgendaItem::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
