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
    private const array GENDERED_ENDINGS = ['ius', 'iai', 'ys', 'as', 'ai', 'es', 'e', 'a'];

    /**
     * Agent-noun stems that can head a duty title, mapped to the masculine singular ending
     * they take — ASCII-folded, because matching happens after Str::ascii() ("iždinink"
     * arrives as "izdinink", "atstovė" as "atstove").
     *
     * The head noun is frequently not the last word ("Studentų atstovas VU FF Taryboje",
     * "SPK atstovas"), so normalising only the tail leaves the masculine and feminine
     * spellings of one duty looking like two different duties. Recognising the noun itself
     * is what makes those collapse.
     *
     * Mirrors DUTY_AGENT_NOUN_STEMS in resources/js/Utils/String.ts — keep the two in step.
     *
     * @var array<string, string>
     */
    private const array AGENT_NOUN_STEMS = [
        'administrator' => 'ius',
        'atstov' => 'as',
        'direktor' => 'ius',
        'instruktor' => 'ius',
        'izdinink' => 'as',
        'koordinator' => 'ius',
        'kurator' => 'ius',
        'mentor' => 'ius',
        'nar' => 'ys',
        'pavaduotoj' => 'as',
        'pirminink' => 'as',
        'prezident' => 'as',
        'redaktor' => 'ius',
        'sekretor' => 'ius',
        'seniun' => 'as',
        'trener' => 'is',
        'vadov' => 'as',
        'vicepirminink' => 'as',
        'viceprezident' => 'as',
    ];

    /** @var array<string, string> */
    private const array MASCULINE_PLURAL_ENDINGS = ['ius' => 'iai', 'as' => 'ai', 'ys' => 'iai', 'is' => 'iai'];

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

        // The head noun first, wherever it sits, then the tail as before — the tail rule
        // still earns its keep for titles no stem covers ("Partnerysčių koodinatorius").
        // When they are the same word the second pass is a no-op, so this stays idempotent.
        $headIndex = self::findAgentNounIndex($words);

        if ($headIndex !== null) {
            $words[$headIndex] = self::normalizeAgentNoun($words[$headIndex]);
        }

        $words[$lastIndex] = self::stripGenderedEnding($words[$lastIndex]);

        return implode(' ', $words).$suffix;
    }

    /**
     * Index of the last word that is a recognised agent noun, or null when the title uses
     * none. Last, because qualifiers stack up to its left ("Chemijos magistras studentų
     * atstove").
     *
     * @param  list<string>  $words
     */
    private static function findAgentNounIndex(array $words): ?int
    {
        $found = null;

        foreach ($words as $index => $word) {
            if (self::matchAgentNounStem($word) !== null) {
                $found = $index;
            }
        }

        return $found;
    }

    /**
     * Returns the stem behind a word if the word is one of that stem's gendered/pluralised
     * spellings, or null otherwise. Non-nominative forms deliberately miss: "atstovu"
     * ("atstovų") is a modifier in "studentu atstovu koordinatore", not the head noun.
     */
    private static function matchAgentNounStem(string $word): ?string
    {
        foreach (self::AGENT_NOUN_STEMS as $stem => $masculineEnding) {
            $forms = [
                $stem.$masculineEnding,
                $stem.self::MASCULINE_PLURAL_ENDINGS[$masculineEnding],
                $stem.'e',
                $stem.'es',
            ];

            if (in_array($word, $forms, true)) {
                return $stem;
            }
        }

        return null;
    }

    /**
     * Replaces an agent noun with its stem plus the same placeholder
     * {@see self::stripGenderedEnding()} uses, so every spelling of it compares equal.
     */
    private static function normalizeAgentNoun(string $word): string
    {
        $stem = self::matchAgentNounStem($word);

        return $stem === null ? $word : $stem.'~';
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
