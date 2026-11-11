<?php

namespace App\Enums;

/** Topics group terms by field; collections group material, e.g. by the training it was made for. */
enum KnowledgeSectionKind: string
{
    case Topic = 'topic';
    case Collection = 'collection';

    public function label(): string
    {
        return __("learning.section_kinds.{$this->value}");
    }
}
