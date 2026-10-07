<?php

namespace App\Services\FileUsage;

use Normalizer;

/**
 * Decides whether a stored value references one uploaded file, however that
 * value was encoded on write.
 *
 * The same `/uploads/files/<path>` reaches the database raw (plain columns),
 * JSON-escaped (`\/`, `\uXXXX`), inside sanitized HTML (`%20`, `&#43;`,
 * `&amp;`), percent-encoded by a browser, NFC or NFD. Rather than enumerate
 * every encoding of the path, the stored value is decoded back to plain text
 * and compared once.
 */
final readonly class FileReferenceMatcher
{
    /** Characters that survive every encoding above unchanged. */
    private const string INVARIANT_RUN = '/[A-Za-z0-9._-]{2,}/';

    private const int MAX_NEEDLES = 4;

    private string $relativePath;

    private string $pattern;

    /**
     * @param  string  $relativePath  path below `public/files/`, e.g. `padaliniai/vusamif/foto.jpg`
     * @param  bool  $matchLegacyPath  whether `/uploads/<path>` (without `files/`) still resolves to this file
     */
    public function __construct(string $relativePath, bool $matchLegacyPath)
    {
        $this->relativePath = self::nfc(ltrim($relativePath, '/'));

        $paths = preg_quote('uploads/files/'.$this->relativePath, '~');

        if ($matchLegacyPath) {
            $paths = '(?:'.$paths.'|'.preg_quote('uploads/'.$this->relativePath, '~').')';
        }

        // Match delimited local paths or vusa.lt URLs to avoid prefix/extension false positives.
        $this->pattern = '~(?:^/?|["\'=(>\s]/?|//(?:[A-Za-z0-9-]+\.)*vusa\.lt/)'
            .$paths
            .'(?=$|[^\p{L}\p{N}._%-])~u';
    }

    /**
     * Substrings every stored form of a reference contains, for a cheap SQL
     * pre-filter. Empty when the path has no ASCII run to anchor on.
     *
     * @return list<string>
     */
    public function coarseNeedles(): array
    {
        preg_match_all(self::INVARIANT_RUN, $this->relativePath, $matches);

        $runs = array_values(array_unique($matches[0]));
        usort($runs, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return array_slice($runs, 0, self::MAX_NEEDLES);
    }

    public function matches(?string $storedValue): bool
    {
        if ($storedValue === null || $storedValue === '') {
            return false;
        }

        foreach ($this->strings($storedValue) as $string) {
            foreach ($this->decodedForms($string) as $form) {
                if (preg_match($this->pattern, $form) === 1) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Every string leaf of a JSON value (ContentPart blocks, Spatie
     * translations, array casts), or the value itself.
     *
     * @return list<string>
     */
    private function strings(string $value): array
    {
        $trimmed = ltrim($value);

        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                $leaves = [];
                array_walk_recursive($decoded, function (mixed $leaf) use (&$leaves): void {
                    if (is_string($leaf)) {
                        $leaves[] = $leaf;
                    }
                });

                return $leaves;
            }
        }

        return [$value];
    }

    /**
     * The value as stored, with HTML entities decoded, and additionally
     * percent-decoded — the undecoded form is kept so a literal `%` in a
     * filename still matches.
     *
     * @return list<string>
     */
    private function decodedForms(string $value): array
    {
        $entityDecoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return array_values(array_unique(array_map(self::nfc(...), [
            $value,
            $entityDecoded,
            rawurldecode($entityDecoded),
        ])));
    }

    private static function nfc(string $value): string
    {
        $normalized = Normalizer::normalize($value, Normalizer::FORM_C);

        return is_string($normalized) ? $normalized : $value;
    }
}
