<?php

namespace App\Support;

/**
 * Normalisation for user-supplied storage paths.
 *
 * Stripping `../` with a single-pass `str_replace` is not safe — `....//` and `..././` both
 * collapse back into `../` once the first pass removes the inner match. This normaliser works
 * on path *segments* instead, so a `..` can never survive into the result no matter how it was
 * spelled, and the returned path is always relative to whatever root the caller prefixes.
 */
final class StoragePath
{
    /**
     * Collapse separators and drop every `.`/`..` segment.
     *
     * Traversal segments are dropped rather than resolved: `a/../../b` becomes `a/b`, never
     * something above the caller's root.
     */
    public static function normalizeRelative(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        $segments = array_filter(
            explode('/', $path),
            static fn (string $segment): bool => $segment !== '' && $segment !== '.' && $segment !== '..'
        );

        return implode('/', $segments);
    }

    /**
     * Whether a path is safe to hand to the storage layer.
     *
     * This is defence in depth, not the traversal guard — normalizeRelative() already makes a
     * `..` structurally impossible. So it rejects only what can never belong in a name:
     * control, format and unassigned characters (\p{C}, which covers NUL and the bidi
     * overrides that let one name render as another), plus invalid UTF-8, on which preg_match
     * returns false rather than 0.
     *
     * Everything a real filename uses is deliberately allowed. The hand-listed allowlist this
     * replaces omitted commas, brackets, quotes, en dashes and ©, so folders that exist on
     * disk — "Tyrimai, ataskaitos" among them — were unreachable behind "Invalid path format".
     */
    public static function isSafeRelative(string $path): bool
    {
        return $path !== '' && preg_match('/^[^\p{C}]+$/u', $path) === 1;
    }

    /**
     * Whether the path contains a traversal segment in any spelling.
     */
    public static function hasTraversal(string $path): bool
    {
        $segments = explode('/', str_replace('\\', '/', $path));

        return in_array('..', $segments, true);
    }
}
