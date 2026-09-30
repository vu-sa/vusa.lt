<?php

namespace App\Models;

use App\Models\Pivots\AgendaItem;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Something done towards a goal, a problem, or both at once (goals pilot). It has no permissions
 * of its own: whoever may update its goal or problem may log it.
 *
 * @property string $id
 * @property string|null $goal_id
 * @property string|null $problem_id
 * @property array|string $title
 * @property array|string|null $description
 * @property Carbon $happened_on
 * @property string|null $agenda_item_id
 * @property int|null $document_id
 * @property string|null $url
 * @property string|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Goal|null $goal
 * @property-read Problem|null $problem
 * @property-read User|null $createdBy
 * @property-read AgendaItem|null $agendaItem
 * @property-read Document|null $document
 * @property-read Collection<int, User> $performers
 *
 * @method static \Database\Factories\StepFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Step query()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'goal_id',
    'problem_id',
    'title',
    'description',
    'happened_on',
    'agenda_item_id',
    'document_id',
    'url',
    'created_by',
])]
class Step extends Model
{
    use HasFactory, HasTranslations, HasUlids, LogsModelActivity;

    public $translatable = ['title', 'description'];

    protected function sanitizedHtmlTranslations(): array
    {
        return ['description'];
    }

    /** @return BelongsTo<Goal, $this> */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /** @return BelongsTo<Problem, $this> */
    public function problem(): BelongsTo
    {
        return $this->belongsTo(Problem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Who did the step, which need not be who recorded it.
     *
     * @return BelongsToMany<User, $this>
     */
    public function performers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'step_user');
    }

    /** @return BelongsTo<AgendaItem, $this> */
    public function agendaItem(): BelongsTo
    {
        return $this->belongsTo(AgendaItem::class);
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    protected function casts(): array
    {
        return [
            'happened_on' => 'date',
        ];
    }
}
