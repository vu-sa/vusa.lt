<?php

namespace App\Enums;

enum LearningProgressStatus: string
{
    case Done = 'done';
    case Skipped = 'skipped';

    public function label(): string
    {
        return __("learning.states.{$this->value}");
    }
}
