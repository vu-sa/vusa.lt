<?php

namespace App\Models;

use App\Enums\SurveyQuestionType;
use App\Models\Traits\HasTranslations;
use Database\Factories\SurveyQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One question belonging to one survey.
 *
 * Either copied from a SurveyQuestionTemplate (survey_question_template_id set, for
 * provenance) or written from scratch (null). Both are first-class — the whole point of
 * keeping the bank in vusa.lt is that a survey can mix them.
 *
 * @property string $id
 * @property string $survey_id
 * @property string|null $survey_question_template_id
 * @property string|null $group_name
 * @property string $title
 * @property SurveyQuestionType $type
 * @property string|null $question
 * @property string|null $help
 * @property array<array-key, mixed>|null $options
 * @property bool $is_required
 * @property int $order
 * @property-read Survey|null $survey
 * @property-read SurveyQuestionTemplate|null $template
 *
 * @method static SurveyQuestionFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[Fillable(['survey_id', 'survey_question_template_id', 'group_name', 'title', 'type', 'question', 'help', 'options', 'is_required', 'order'])]
class SurveyQuestion extends Model
{
    use HasFactory, HasTranslations, HasUlids;

    /** @var array<int, string> */
    public $translatable = ['group_name', 'question', 'help'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'type' => SurveyQuestionType::class,
            'options' => 'array',
            'is_required' => 'boolean',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestionTemplate::class, 'survey_question_template_id');
    }

    /**
     * Answer options normalised for the .lss builder.
     *
     * Tolerates half-filled rows from the admin UI: an option without a code or without any
     * label is dropped rather than emitted as a broken answer row.
     *
     * @return list<array{code: string, label: array<string, string>}>
     */
    public function normalizedOptions(): array
    {
        if (! $this->type->hasOptions() || ! is_array($this->options)) {
            return [];
        }

        $normalized = [];

        foreach ($this->options as $option) {
            if (! is_array($option)) {
                continue;
            }

            $code = isset($option['code']) ? trim((string) $option['code']) : '';
            $label = $option['label'] ?? [];

            if ($code === '' || ! is_array($label) || $label === []) {
                continue;
            }

            $normalized[] = [
                'code' => $code,
                'label' => array_map(fn ($value): string => (string) $value, $label),
            ];
        }

        return $normalized;
    }
}
