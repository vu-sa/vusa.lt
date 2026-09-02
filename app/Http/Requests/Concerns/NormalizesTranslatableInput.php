<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Arr;

/**
 * Lets a translatable field accept either shape on the wire.
 *
 * A plain string is filed under `lt` — never under the request locale. Most callers (the
 * action-window meeting wizard, the bulk agenda paste box, the meeting API, older tests)
 * post plain Lithuanian strings, and an admin working in the English interface must not
 * have their Lithuanian agenda land in the `en` slot.
 */
trait NormalizesTranslatableInput
{
    /**
     * @param  string  ...$keys  Dot paths, `*` supported (e.g. `votes.*.title`).
     */
    protected function normalizeTranslatable(string ...$keys): void
    {
        $input = $this->all();
        $changed = false;

        foreach ($keys as $key) {
            foreach (array_keys($this->wildcardMatches($input, $key)) as $path) {
                $value = Arr::get($input, $path);

                if (! is_string($value)) {
                    continue;
                }

                Arr::set($input, $path, ['lt' => $value]);
                $changed = true;
            }
        }

        if ($changed) {
            $this->merge($input);
        }
    }

    /**
     * Every concrete path `$key` resolves to, as a `path => true` map. Built by hand rather
     * than with `Arr::dot()` on the whole payload, so a value that is itself an array
     * (an already-translated `{lt, en}`) is not flattened into unreachable leaf paths.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, true>
     */
    private function wildcardMatches(array $input, string $key): array
    {
        $paths = [''];

        foreach (explode('.', $key) as $segment) {
            $next = [];

            foreach ($paths as $prefix) {
                if ($segment !== '*') {
                    $next[] = $prefix === '' ? $segment : $prefix.'.'.$segment;

                    continue;
                }

                $branch = $prefix === '' ? $input : Arr::get($input, $prefix);

                foreach (is_array($branch) ? array_keys($branch) : [] as $index) {
                    $next[] = $prefix === '' ? (string) $index : $prefix.'.'.$index;
                }
            }

            $paths = $next;
        }

        return array_fill_keys(array_filter($paths, fn (string $path): bool => Arr::has($input, $path)), true);
    }
}
