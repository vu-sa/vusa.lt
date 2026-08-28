<?php

namespace App\Support\Docs;

/**
 * The tests a branch added to or removed from one test file.
 */
class ChangedTestFile
{
    /**
     * @param  list<string>  $added
     * @param  list<string>  $removed
     */
    public function __construct(
        public readonly string $path,
        public readonly array $added,
        public readonly array $removed,
    ) {}
}
