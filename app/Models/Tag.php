<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string|null $alias
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property array|string|null $name
 * @property array|string|null $description
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\News> $news
 * @property-read mixed $translations
 * @method static \Database\Factories\TagFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereLocales(string $column, array $locales)
 * @mixin \Eloquent
 */
class Tag extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'description',
        'alias',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generate alias if not provided
        static::saving(function ($tag) {
            if (empty($tag->alias)) {
                $tag->alias = $tag->generateAlias();
            }
        });

        // When a tag is being deleted, detach it from all related news and pages
        static::deleting(function ($tag) {
            $tag->news()->detach();
            // Future: detach from pages when implemented
            // $tag->pages()->detach();
        });
    }

    public function news(): BelongsToMany
    {
        return $this->belongsToMany(News::class, 'posts_tags', 'tag_id', 'news_id');
    }

    // Future: add pages relationship when implemented
    // public function pages(): BelongsToMany
    // {
    //     return $this->belongsToMany(Page::class, 'posts_tags', 'tag_id', 'page_id');
    // }

    /**
     * Generate a URL-friendly alias from the tag name
     */
    protected function generateAlias(): string
    {
        // Get the Lithuanian name first, fallback to English, then to any available translation
        $name = $this->getTranslation('name', 'lt')
            ?? $this->getTranslation('name', 'en')
            ?? (is_array($this->name) ? collect($this->name)->first() : $this->name);

        if (empty($name)) {
            // Fallback to ID-based alias if no name is available
            return 'tag-'.($this->id ?? uniqid());
        }

        // Create a URL-friendly slug
        $baseAlias = Str::slug($name);

        // Ensure uniqueness by checking for existing aliases
        $alias = $baseAlias;
        $counter = 1;

        while (static::where('alias', $alias)->where('id', '!=', $this->id ?? 0)->exists()) {
            $alias = $baseAlias.'-'.$counter;
            $counter++;
        }

        return $alias;
    }
}
