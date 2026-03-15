<?php

namespace App\Models;

use Database\Factories\PlanningResourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property int $academic_year_start
 * @property string $title
 * @property string $type
 * @property string|null $content
 * @property string|null $category
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static \Database\Factories\PlanningResourceFactory factory($count = null, $state = [])
 * @method static Builder<static>|PlanningResource newModelQuery()
 * @method static Builder<static>|PlanningResource newQuery()
 * @method static Builder<static>|PlanningResource query()
 * @method static Builder<static>|PlanningResource forAcademicYear(int $year)
 * @method static Builder<static>|PlanningResource ordered()
 *
 * @mixin \Eloquent
 */
class PlanningResource extends Model implements HasMedia
{
    /** @use HasFactory<PlanningResourceFactory> */
    use HasFactory, HasUlids, InteractsWithMedia;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'academic_year_start' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('resource_file')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->useDisk('spatieMediaLibrary');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForAcademicYear(Builder $query, int $year): Builder
    {
        return $query->where('academic_year_start', $year);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }
}
