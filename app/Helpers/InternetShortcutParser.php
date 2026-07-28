<?php

namespace App\Helpers;

/**
 * Parses Windows `.url` internet-shortcut files.
 *
 * These are plain-text INI-like files SharePoint stores verbatim, e.g.:
 *
 *   [InternetShortcut]
 *   URL=https://ataskaita2023.vusa.lt
 *   IconFile=https://...
 *   IconIndex=0
 *
 * `parse_ini_string()` is deliberately avoided: some shortcuts carry a
 * trailing shell-extension section like `[{000214A0-0000-0000-C000-000000000046}]`
 * that is not a well-formed INI section for every scanner mode, and query
 * strings containing `;` or `=` can confuse the built-in scanner. A small,
 * deterministic line scanner is used instead.
 */
class InternetShortcutParser
{
    /**
     * Extract the target URL from the contents of a `.url` file.
     *
     * Returns null when there is no usable http(s) target — including when
     * the file is empty, malformed, or points at a non-http(s) scheme.
     */
    public static function parse(?string $contents): ?string
    {
        if ($contents === null || trim($contents) === '') {
            return null;
        }

        // Strip a leading BOM and normalize line endings to \n.
        $contents = preg_replace('/^(?:\xEF\xBB\xBF|\xFF\xFE|\xFE\xFF)/', '', $contents);
        $contents = str_replace(["\r\n", "\r"], "\n", $contents);

        $inShortcutSection = false;

        foreach (explode("\n", $contents) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, ';')) {
                continue;
            }

            // Section header, e.g. [InternetShortcut] or [{000214A0-...}].
            if (str_starts_with($line, '[')) {
                $inShortcutSection = strcasecmp(trim($line, "[] \t"), 'InternetShortcut') === 0;

                continue;
            }

            if (! $inShortcutSection || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            // Ignore IconFile, IconIndex, HotKey, Modified, IDList, etc.
            if (strcasecmp(trim($key), 'URL') !== 0) {
                continue;
            }

            return self::sanitize(trim($value));
        }

        return null;
    }

    /**
     * Only http(s) targets are accepted. The parsed value is rendered
     * directly as a user-facing href, so a `javascript:` or `file:` value
     * coming out of SharePoint-stored content must never reach the browser.
     */
    private static function sanitize(string $url): ?string
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }
}
