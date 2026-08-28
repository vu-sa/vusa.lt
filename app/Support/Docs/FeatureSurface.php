<?php

namespace App\Support\Docs;

/**
 * The app's feature areas and how well each is documented — the denominator the
 * coverage report and dashboard are keyed on.
 */
class FeatureSurface
{
    /**
     * @param  array<string, FeatureArea>  $areas  keyed by slug, sorted
     */
    public function __construct(
        public readonly array $areas,
    ) {}

    /**
     * @return list<FeatureArea>
     */
    public function documented(): array
    {
        return array_values(array_filter($this->areas, fn (FeatureArea $a) => $a->isDocumented()));
    }

    /**
     * Areas with real behaviour behind them that no page explains yet — the
     * writing backlog, ranked so two people know what to pick up first.
     *
     * @return list<FeatureArea>
     */
    public function backlog(): array
    {
        $undocumented = array_filter(
            $this->areas,
            fn (FeatureArea $a) => ! $a->isDocumented() && $a->isTested(),
        );

        usort($undocumented, fn (FeatureArea $a, FeatureArea $b) => $b->priority() <=> $a->priority() ?: strcmp($a->slug, $b->slug));

        return $undocumented;
    }

    public function documentedCount(): int
    {
        return count($this->documented());
    }

    public function withHelpCount(): int
    {
        return count(array_filter($this->areas, fn (FeatureArea $a) => $a->hasHelp));
    }
}
