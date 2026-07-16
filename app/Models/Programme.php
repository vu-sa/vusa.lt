<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Database\Factories\ProgrammeFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property array|string $title
 * @property array|string|null $description
 * @property string $start_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, ProgrammeDay> $days
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, Training> $trainings
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\ProgrammeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Programme newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Programme newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Programme query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Programme whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Programme whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Programme whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Programme whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class Programme extends Model
{
    /** @use HasFactory<ProgrammeFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = ['title', 'description'];

    public $translatable = ['title', 'description'];

    public function days()
    {
        return $this->hasMany(ProgrammeDay::class);
    }

    /**
     * Inverse of {@see Training::programmes()}.
     *
     * Ownership lives in the `programmables` pivot table — the `programmes`
     * table itself carries no `programmable_id`/`programmable_type` columns.
     */
    public function trainings(): MorphToMany
    {
        return $this->morphedByMany(Training::class, 'programmable');
    }

    /**
     * The training that owns this programme. Authorization for every programme
     * mutation is delegated to this training's policy, since programmes carry
     * no permissions of their own.
     */
    public function owningTraining(): ?Training
    {
        /** @var Training|null $training */
        $training = $this->trainings()->first();

        return $training;
    }
}
