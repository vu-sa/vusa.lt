<?php

namespace App\Models;

use App\Models\Pivots\AgendaItem;
use Database\Factories\AgendaItemNoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Private, real-time collaborative notes ("Atstovų pastabos") for an agenda item.
 *
 * @property string $id
 * @property string $agenda_item_id
 * @property string|null $yjs_state base64-encoded Y.js document snapshot
 * @property string|null $notes_html rendered HTML snapshot for read-only display
 * @property string|null $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read AgendaItem|null $agendaItem
 * @property-read User|null $editor
 */
class AgendaItemNote extends Model
{
    /** @use HasFactory<AgendaItemNoteFactory> */
    use HasFactory, HasUlids;

    protected $guarded = [];

    /**
     * The raw CRDT state is never serialized by default; it is only ever exposed
     * through the dedicated admin API endpoint.
     *
     * @var list<string>
     */
    protected $hidden = ['yjs_state'];

    public function agendaItem(): BelongsTo
    {
        return $this->belongsTo(AgendaItem::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
