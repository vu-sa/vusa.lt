<?php

namespace App\Models;

use App\Models\Pivots\Dutiable;
use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property array|string $name
 * @property string $degree
 * @property int $tenant_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Dutiable> $dutiables
 * @property-read Tenant $tenant
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\StudyProgramFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudyProgram newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudyProgram newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudyProgram query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudyProgram whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudyProgram whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudyProgram whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudyProgram whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class StudyProgram extends Model
{
    use HasFactory, HasTranslations, HasUlids;

    public $translatable = ['name'];

    protected $fillable = [
        'name',
        'degree',
        'tenant_id',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function dutiables(): HasMany
    {
        return $this->hasMany(Dutiable::class);
    }
}
