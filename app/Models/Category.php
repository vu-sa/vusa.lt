<?php

namespace App\Models;

use App\Contracts\GuardsForceDelete;
use App\Models\Traits\GuardsForceDeleteWhenReferenced;
use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $alias
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $name
 * @property string|null $description
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Calendar> $calendars
 * @property-read string|null $force_delete_blocked_reason
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, News> $news
 * @property-read Collection<int, Page> $pages
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\CategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'name',
    'alias',
    'description',
])]
class Category extends Model implements GuardsForceDelete
{
    use GuardsForceDeleteWhenReferenced, HasFactory, HasTranslations, SoftDeletes;

    public $translatable = ['name', 'description'];

    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    public function news()
    {
        return $this->hasMany(News::class);
    }

    public function calendars(): HasMany
    {
        return $this->hasMany(Calendar::class);
    }

    /**
     * `news.category_id` restricts deletes.
     */
    public function forceDeleteBlockedReason(): ?string
    {
        return $this->forceDeleteReasonFor([
            'entities.news.model' => $this->countedRelation('news'),
        ]);
    }
}
