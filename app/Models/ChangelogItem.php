<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property array<array-key, mixed> $title
 * @property \Illuminate\Support\Carbon $date
 * @property array<array-key, mixed> $description
 * @property string|null $permission_id
 * @property-read mixed $translations
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangelogItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangelogItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangelogItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangelogItem whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangelogItem whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangelogItem whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChangelogItem whereLocales(string $column, array $locales)
 * @mixin \Eloquent
 */
class ChangelogItem extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['title', 'description'];

    public $timestamps = false;

    protected $casts = [
        'date' => 'datetime',
    ];

    protected $fillable = [
        'title',
        'description',
        'date',
    ];
}
