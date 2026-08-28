<?php

namespace App\Support\Docs;

/**
 * The routed surface the test suite references, as read from test source.
 */
class TestSurface
{
    /**
     * @param  array<string, list<string>>  $routes  route name => test files naming it
     * @param  array<string, list<string>>  $assertions  test file => meaningful assertions used
     */
    public function __construct(
        public readonly array $routes,
        public readonly array $assertions,
        public readonly int $fileCount,
        public readonly int $testCount,
    ) {}
}
