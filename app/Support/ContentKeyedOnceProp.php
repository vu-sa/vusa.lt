<?php

namespace App\Support;

use Inertia\OnceProp;

/**
 * A once prop keyed by its own content, so clients refetch it only after it changes. The value
 * is resolved on first use, never for redirects or non-Inertia responses.
 */
final class ContentKeyedOnceProp extends OnceProp
{
    private bool $resolved = false;

    private mixed $value = null;

    public function __construct(private readonly string $name, callable $callback)
    {
        parent::__construct($callback);
    }

    #[\Override]
    public function __invoke(): mixed
    {
        if (! $this->resolved) {
            $this->value = parent::__invoke();
            $this->resolved = true;
        }

        return $this->value;
    }

    #[\Override]
    public function getKey(): string
    {
        return $this->name.':'.app()->getLocale().':'.hash('xxh128', json_encode($this(), JSON_THROW_ON_ERROR));
    }
}
