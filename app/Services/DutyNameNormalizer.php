<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Reduces a duty name to a form that is equal across its gendered/pluralised
 * variants, so "Komunikacijos koordinatorius" and "Komunikacijos koordinatorė"
 * compare equal while "Studentų atstovas" and "Studentų atstovas SPK" do not.
 *
 * This is deliberately simpler than the client-side inflector in
 * resources/js/Utils/String.ts (which *generates* the gendered forms) — here we
 * only need to know whether two names denote the same duty, not produce a form
 * that reads correctly in a sentence. False positives are acceptable: callers
 * use this to power an advisory warning, never a block.
 */
class DutyNameNormalizer
{
    /**
     * Gendered/pluralised noun endings Lithuanian job titles take, longest first
     * so a token is matched against its true suffix rather than a shorter
     * substring of it (e.g. "iai" before "ai").
     *
     * @var list<string>
     */
    private const GENDERED_ENDINGS = ['ius', 'iai', 'ys', 'as', 'ai', 'es', 'e', 'a'];

    /**
     * @return string the normalized form; equal for names that denote the same duty
     */
    public static function normalize(string $name): string
    {
        $folded = mb_strtolower(Str::ascii(trim($name)));

        // Strip "(-ė)", "(-čių)" style gender markers, wherever they appear —
        // admins write these both as a suffix and, occasionally, mid-title.
        $stripped = preg_replace('/\s*\(\s*-[^)]*\)/u', '', $folded) ?? $folded;
        $stripped = trim(preg_replace('/\s+/u', ' ', $stripped) ?? $stripped);

        if ($stripped === '') {
            return '';
        }

        // A trailing "(...)" that survived (e.g. "(biochemija)") names a real
        // variant, not a gender — keep it verbatim and normalise only the stem.
        $stem = $stripped;
        $suffix = '';

        if (preg_match('/^(.*?)\s*(\([^)]*\))$/u', $stripped, $matches) === 1) {
            $stem = $matches[1];
            $suffix = ' '.$matches[2];
        }

        if ($stem === '') {
            return trim($suffix);
        }

        $words = explode(' ', $stem);
        $lastIndex = array_key_last($words);
        $words[$lastIndex] = self::stripGenderedEnding($words[$lastIndex]);

        return implode(' ', $words).$suffix;
    }

    /**
     * Replaces a word's trailing gendered ending with a placeholder, so two
     * spellings of the same word normalise to the same string. Leaves the word
     * untouched if stripping the ending would consume it entirely (guards tiny
     * words like "a" or "es" that happen to equal an ending).
     */
    private static function stripGenderedEnding(string $word): string
    {
        foreach (self::GENDERED_ENDINGS as $ending) {
            if (mb_strlen($word) > mb_strlen($ending) && str_ends_with($word, $ending)) {
                return mb_substr($word, 0, -mb_strlen($ending)).'~';
            }
        }

        return $word;
    }
}
