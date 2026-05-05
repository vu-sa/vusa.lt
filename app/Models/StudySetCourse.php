<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Database\Factories\StudySetCourseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $study_set_id
 * @property string|null $name
 * @property int $order
 * @property string $semester
 * @property int $credits
 * @property bool $is_visible
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read StudySet $studySet
 * @property-read Collection<int, LecturerReview> $reviews
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\StudySetCourseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySetCourse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySetCourse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudySetCourse query()
 *
 * @mixin \Eloquent
 */
class StudySetCourse extends Model
{
    /** @use HasFactory<StudySetCourseFactory> */
    use HasFactory, HasTranslations, HasUlids;

    public $translatable = ['name'];

    protected $fillable = [
        'study_set_id',
        'name',
        'order',
        'semester',
        'credits',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function studySet(): BelongsTo
    {
        return $this->belongsTo(StudySet::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(LecturerReview::class);
    }
}
