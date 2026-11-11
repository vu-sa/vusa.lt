<?php

namespace App\Enums;

/** A term is a glossary entry; material (slides, examples, a unit's own regulations) is read about a term. */
enum KnowledgeItemKind: string
{
    case Term = 'term';
    case Material = 'material';

    public function label(): string
    {
        return __("learning.kinds.{$this->value}");
    }

    public function sectionKind(): KnowledgeSectionKind
    {
        return $this === self::Term ? KnowledgeSectionKind::Topic : KnowledgeSectionKind::Collection;
    }
}
