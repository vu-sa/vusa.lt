<?php

namespace App\Support\Docs;

/**
 * One feature area of the app — the unit an admin thinks in ("reservations",
 * "duties"), not a route name. Most areas map to a domain model; some (search,
 * dashboard) are cross-model workflows with no single model behind them.
 */
class FeatureArea
{
    /**
     * @param  string  $slug  route-area prefix as it appears in route names (e.g. `reservations`)
     * @param  string|null  $modelAlias  the MorphMap alias this area resolves to, or null
     * @param  class-string|null  $modelClass  the model class, or null when the area has no model
     * @param  list<string>  $routes  every named route in the area
     * @param  list<string>  $testedRoutes  routes some test file names
     * @param  list<string>  $docPages  doc pages that document this area
     * @param  bool  $isAdmin  the area is reachable under `/mano`
     */
    public function __construct(
        public readonly string $slug,
        public readonly ?string $modelAlias,
        public readonly ?string $modelClass,
        public readonly array $routes,
        public readonly array $testedRoutes,
        public readonly array $docPages,
        public readonly bool $isAdmin,
    ) {}

    public function isDocumented(): bool
    {
        return $this->docPages !== [];
    }

    public function isTested(): bool
    {
        return $this->testedRoutes !== [];
    }

    public function testedRatio(): float
    {
        return $this->routes === [] ? 1.0 : count($this->testedRoutes) / count($this->routes);
    }

    /**
     * How much this area is worth documenting first: a bigger tested surface is
     * more behaviour to explain, and admin areas matter more than public ones to
     * the people these docs serve.
     */
    public function priority(): int
    {
        return count($this->testedRoutes) * 3
            + ($this->isAdmin ? 4 : 0);
    }
}
