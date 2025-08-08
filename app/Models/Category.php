<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $alias
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property array|string|null $name
 * @property array|string|null $description
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Calendar> $calendars
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\News> $news
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Page> $pages
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\CategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class Category extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'alias',
        'description',
    ];

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
}
