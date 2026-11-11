<?php

namespace App\Enums;

enum LearningImportance: string
{
    case Essential = 'essential';
    case Recommended = 'recommended';
    case Optional = 'optional';

    public function label(): string
    {
        return __("learning.importance.{$this->value}");
    }

    public function isSkippable(): bool
    {
        return $this !== self::Essential;
    }
}
