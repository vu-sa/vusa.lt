<?php

namespace App\Enums;

enum LearningStepKind: string
{
    case Knowledge = 'knowledge';
    case Action = 'action';
    case MeetingPrep = 'meeting_prep';

    public function label(): string
    {
        return __("learning.kinds.{$this->value}");
    }
}
