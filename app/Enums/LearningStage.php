<?php

namespace App\Enums;

enum LearningStage: string
{
    case BeforeStart = 'before_start';
    case FirstMonths = 'first_months';
    case WholeTerm = 'whole_term';
    case TermEnd = 'term_end';

    public function label(): string
    {
        return __("learning.stages.{$this->value}");
    }

    public function order(): int
    {
        return match ($this) {
            self::BeforeStart => 0,
            self::FirstMonths => 1,
            self::WholeTerm => 2,
            self::TermEnd => 3,
        };
    }
}
