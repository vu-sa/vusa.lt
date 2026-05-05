<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Database\Factories\LecturerReviewFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $study_set_course_id
 * @property string|null $lecturer
 * @property string|null $comment
 * @property bool $is_visible
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read StudySetCourse $course
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\LecturerReviewFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturerReview newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturerReview newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturerReview query()
 *
 * @mixin \Eloquent
 */
class LecturerReview extends Model
{
    /** @use HasFactory<LecturerReviewFactory> */
    use HasFactory, HasTranslations, HasUlids;

    public $translatable = ['lecturer', 'comment'];

    protected $fillable = [
        'study_set_course_id',
        'lecturer',
        'comment',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(StudySetCourse::class, 'study_set_course_id');
    }
}
