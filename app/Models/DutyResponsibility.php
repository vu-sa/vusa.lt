<?php

namespace App\Models;

use App\Enums\Responsibility;
use App\Enums\ResponsibilityScope;
use App\Models\Traits\LogsModelActivity;
use App\Services\ResponsibilityResolver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * A duty's responsibility for a padalinys, an institution type or one institution.
 *
 * @property string $id
 * @property string $duty_id
 * @property Responsibility $responsibility
 * @property string $scope_type morph alias, see coverage()
 * @property string $scope_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Duty $duty
 * @property-read Model|null $scope
 *
 * @mixin \Eloquent
 */
#[Table(name: 'duty_responsibilities')]
#[Fillable(['duty_id', 'responsibility', 'scope_type', 'scope_id'])]
class DutyResponsibility extends Model
{
    use HasFactory, HasUlids, LogsModelActivity;

    #[\Override]
    protected static function booted(): void
    {
        static::saved(fn () => app(ResponsibilityResolver::class)->flush());
        static::deleted(fn () => app(ResponsibilityResolver::class)->flush());
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'responsibility' => Responsibility::class,
        ];
    }

    /**
     * What the assignment covers. `scope_type` stays an uncast morph alias like every other
     * polymorphic column; the enum's backing values are those aliases.
     */
    public function coverage(): ResponsibilityScope
    {
        return ResponsibilityScope::from($this->scope_type);
    }

    /**
     * @return BelongsTo<Duty, $this>
     */
    public function duty(): BelongsTo
    {
        return $this->belongsTo(Duty::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function scope(): MorphTo
    {
        return $this->morphTo('scope', 'scope_type', 'scope_id');
    }
}
