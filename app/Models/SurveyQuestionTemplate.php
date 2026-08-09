<?php

namespace App\Models;

use App\Enums\SurveyQuestionType;
use App\Models\Traits\HasTranslations;
use Database\Factories\SurveyQuestionTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A reusable question in the survey question bank.
 *
 * Templates are copied into a survey, not referenced by it — see SurveyQuestion.
 *
 * @property string $id
 * @property int|null $tenant_id
 * @property string|null $group_name
 * @property string $title
 * @property SurveyQuestionType $type
 * @property string|null $question
 * @property string|null $help
 * @property array<array-key, mixed>|null $options
 * @property bool $is_required
 * @property int $order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Tenant|null $tenant
 *
 * @method static SurveyQuestionTemplateFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[Fillable(['tenant_id', 'group_name', 'title', 'type', 'question', 'help', 'options', 'is_required', 'order', 'is_active'])]
class SurveyQuestionTemplate extends Model
{
    use HasFactory, HasTranslations, HasUlids, SoftDeletes;

    /** @var array<int, string> */
    public $translatable = ['group_name', 'question', 'help'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'type' => SurveyQuestionType::class,
            'options' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Templates a given tenant may use: its own, plus the global ones.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAvailableTo($query, ?int $tenantId)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId));
    }

    /**
     * The payload copied onto a survey when this template is attached.
     *
     * Translations are taken whole (toFullArray-style) so the copy keeps both locales.
     *
     * @return array<string, mixed>
     */
    public function toSurveyQuestionAttributes(): array
    {
        return [
            'survey_question_template_id' => $this->id,
            'group_name' => $this->getTranslations('group_name'),
            'title' => $this->title,
            'type' => $this->type,
            'question' => $this->getTranslations('question'),
            'help' => $this->getTranslations('help'),
            'options' => $this->options,
            'is_required' => $this->is_required,
        ];
    }
}
