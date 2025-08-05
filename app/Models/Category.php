<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $alias
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Calendar> $calendars
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\News> $news
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Page> $pages
 *
 * @method static \Database\Factories\CategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 *
 * @mixin \Eloquent
 */
class Category extends Model
{
    use HasFactory;

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
