<?php

namespace App\Enums;

/**
 * The subset of LimeSurvey question types this integration can generate.
 *
 * Values are LimeSurvey's own one-character type codes and go straight into the `type`
 * column of the generated .lss document — do not rename them.
 *
 * Array types (F, A, ...) are intentionally absent: they need `subquestions` rows in the
 * .lss on top of `answers`, which is a second dimension the prototype does not model. Add
 * them by extending LimeSurveyLssBuilder, not by adding a case here alone.
 *
 * @typescript
 */
enum SurveyQuestionType: string
{
    case ShortText = 'S';
    case LongText = 'T';
    case List = 'L';
    case MultipleChoice = 'M';
    case FivePoint = '5';

    public function label(): string
    {
        return match ($this) {
            self::ShortText => __('surveys.question_type.short_text'),
            self::LongText => __('surveys.question_type.long_text'),
            self::List => __('surveys.question_type.list'),
            self::MultipleChoice => __('surveys.question_type.multiple_choice'),
            self::FivePoint => __('surveys.question_type.five_point'),
        };
    }

    /**
     * Whether the question carries author-defined answer options.
     *
     * FivePoint does not: LimeSurvey renders the 1–5 scale itself.
     */
    public function hasOptions(): bool
    {
        return in_array($this, [self::List, self::MultipleChoice], true);
    }

    /**
     * LimeSurvey 6+ addresses question types by theme name as well as type code.
     */
    public function themeName(): string
    {
        return match ($this) {
            self::ShortText => 'shortfreetext',
            self::LongText => 'longfreetext',
            self::List => 'listradio',
            self::MultipleChoice => 'multiplechoice',
            self::FivePoint => 'fivepointchoice',
        };
    }

    /**
     * @return list<array{value: string, label: string, hasOptions: bool}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'hasOptions' => $case->hasOptions(),
            ],
            self::cases(),
        );
    }
}
