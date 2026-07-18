<?php

namespace App\Models;

use App\Services\HtmlSanitizerService;
use Database\Factories\WorkspaceDocumentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A named real-time collaborative document inside a workspace. Mirrors
 * {@see AgendaItemNote}: the Y.js CRDT state is the source of truth, the HTML
 * column is a rendered snapshot for read-only display. Archived = soft-deleted.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $title
 * @property string|null $yjs_state
 * @property string|null $content_html
 * @property string|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Workspace $workspace
 * @property-read User|null $editor
 *
 * @method static \Database\Factories\WorkspaceDocumentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceDocument newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceDocument query()
 *
 * @mixin \Eloquent
 */
class WorkspaceDocument extends Model
{
    /** @use HasFactory<WorkspaceDocumentFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $guarded = [];

    /**
     * The raw CRDT state is never serialized by default; it is only ever exposed
     * through the dedicated admin API endpoint.
     *
     * @var list<string>
     */
    protected $hidden = ['yjs_state'];

    /**
     * The rendered snapshot is authored by any workspace collaborator and
     * re-served to all the others through `v-html`, so it is sanitized on the
     * way in. The CRDT state in `yjs_state` remains the source of truth — this
     * column only ever feeds display.
     */
    protected function contentHtml(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value === null
                ? null
                : app(HtmlSanitizerService::class)->sanitizeRichContent($value),
        );
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
