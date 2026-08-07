<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Finds existing users who look like somebody about to be created.
 *
 * There should be exactly one record per person, but the only thing enforcing that
 * is the unique email — so the same student is routinely re-created under a second
 * address by a second unit, and cleaning it up afterwards needs `users.update.*`.
 *
 * Deliberately plain string work rather than trigram or Levenshtein infrastructure:
 * MySQL's SOUNDEX is built for English and is useless for Lithuanian, and this runs
 * against a few thousand rows behind a debounce. Matching is intentionally generous —
 * these are warnings an admin can ignore, never a block.
 */
class UserSimilarityFinder
{
    /** How many matches to surface at most. */
    private const MATCH_LIMIT = 5;

    /** Strongest first, so the most convincing match is never pushed off the list. */
    private const REASON_ORDER = ['email', 'email_local_part', 'name', 'name_variant'];

    /**
     * Whitespace or any Unicode dash (`\p{Pd}` covers hyphen-minus, non-breaking
     * hyphen and en/em dashes).
     *
     * Double-barrelled surnames are common here, and admins write them
     * inconsistently — "Pavardė-Pavardienė" one time, "Pavardė Pavardienė" the
     * next, or just "Pavardė". Splitting on the dash makes all three the same set
     * of parts, so the rules below see them as one person.
     */
    private const NAME_SPLIT = '/[\s\p{Pd}]+/u';

    /**
     * A match needs a whole identity in common, not one name part.
     *
     * Sharing a single part is worthless as a duplicate signal — "Justinas" and
     * "Lisauskas" are each held by plenty of unrelated people, and surfacing them
     * trains admins to dismiss the warning without reading it. So a name match
     * requires **at least two** parts in common, with one side's parts wholly
     * contained in the other's.
     *
     * @return Collection<int, array{user: User, reason: string}>
     */
    public function find(string $name, string $email): Collection
    {
        $name = trim($name);
        $email = trim($email);

        $tokens = self::nameTokens($name);
        $localPart = self::emailLocalPart($email);

        // One name part can never satisfy the two-part rule, so without an email
        // there is nothing worth asking the database for.
        if (count($tokens) < 2 && $email === '') {
            return collect();
        }

        return $this->candidates($name, $email)
            ->map(fn (User $user) => [
                'user' => $user,
                'reason' => $this->classify($user, $tokens, $email, $localPart),
            ])
            ->filter(fn (array $match) => $match['reason'] !== null)
            ->sortBy(fn (array $match) => array_search($match['reason'], self::REASON_ORDER, true))
            ->take(self::MATCH_LIMIT)
            ->values();
    }

    /**
     * Why this candidate is (or is not) the same person.
     *
     * @param  list<string>  $tokens  Normalised name parts of the person being created
     */
    private function classify(User $user, array $tokens, string $email, string $localPart): ?string
    {
        if ($email !== '' && mb_strtolower((string) $user->email) === mb_strtolower($email)) {
            return 'email';
        }

        if ($localPart !== '' && self::emailLocalPart((string) $user->email) === $localPart) {
            return 'email_local_part';
        }

        $candidateTokens = self::nameTokens((string) $user->name);

        if ($tokens === [] || $candidateTokens === []) {
            return null;
        }

        // Both sides are lowercased, diacritic-stripped and sorted, so this also
        // catches "Ciurlionis Jonas" against "Jonas Čiurlionis".
        if ($tokens === $candidateTokens) {
            return 'name';
        }

        // Middle names: "Justinas Petras Lisauskas" vs "Justinas Lisauskas". One side's
        // parts must be wholly contained in the other's — a partial overlap such as
        // "Justinas Lisauskas" vs "Justinas Petraitis" is two different people.
        $shared = array_intersect($tokens, $candidateTokens);
        $isSubset = count($shared) === min(count($tokens), count($candidateTokens));

        return $isSubset && count($shared) >= 2 ? 'name_variant' : null;
    }

    /**
     * Narrow the table down in SQL before comparing normalised forms in PHP.
     *
     * Candidates must already share **two** name parts, matched as an OR over every
     * pair. A single `LIKE %Justinas%` would both flood the result set and risk the
     * row limit truncating away the one real match.
     *
     * @return Collection<int, User>
     */
    private function candidates(string $name, string $email): Collection
    {
        // Raw (not normalised) parts, turned into LIKE patterns that survive this
        // column's collation. Split on dashes too, so half of a double-barrelled
        // surname still matches however the stored record happens to be punctuated.
        $tokens = collect(preg_split(self::NAME_SPLIT, $name, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->filter(fn (string $token) => mb_strlen($token) >= 3)
            ->map(fn (string $token) => self::likePattern($token))
            ->values()
            ->take(4);

        $pairs = [];
        for ($i = 0; $i < $tokens->count(); $i++) {
            for ($j = $i + 1; $j < $tokens->count(); $j++) {
                $pairs[] = [$tokens[$i], $tokens[$j]];
            }
        }

        if ($pairs === [] && $email === '') {
            return collect();
        }

        return User::query()
            ->select('id', 'name', 'email')
            ->withCount('duties')
            // Columns must be table-qualified: tenants is a hasManyDeep whose joins
            // give several tables an `id`, so a bare `id` is ambiguous.
            ->with('tenants:tenants.id,tenants.shortname')
            ->where(function ($query) use ($pairs, $email): void {
                foreach ($pairs as [$first, $second]) {
                    $query->orWhere(function ($pair) use ($first, $second): void {
                        $pair->whereLike('name', "%{$first}%", false)
                            ->whereLike('name', "%{$second}%", false);
                    });
                }

                if ($email !== '') {
                    $query->orWhereLike('email', "%{$email}%", false);

                    if (($local = self::emailLocalPart($email)) !== '') {
                        $query->orWhereLike('email', "{$local}@%", false);
                    }
                }
            })
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    /**
     * Turn a typed name part into a LIKE pattern this column's collation can match.
     *
     * `users.name` collates as `utf8mb4_lithuanian_ci`, which folds ą ę ė į ų ū to
     * their base letters but treats **č, š and ž as distinct letters** — so
     * `LIKE '%Ciurlionis%'` never finds "Čiurlionis". Since we cannot know which
     * spelling the admin meant, that one family of letters becomes a single-character
     * wildcard, which matches either way round.
     *
     * The rest of the token is left exactly as typed rather than ASCII-folded: the
     * folding of ą/ę/ė/į/ų/ū is the collation's job, and SQLite (used by the test
     * suite) has no such collation to lean on.
     *
     * This only widens the candidate set; find() still decides what counts as a
     * match, on properly normalised names.
     */
    private static function likePattern(string $token): string
    {
        // Escape first: the wildcards below are ours to add, not the user's.
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $token);

        return str_replace(
            ['c', 'č', 's', 'š', 'z', 'ž', 'C', 'Č', 'S', 'Š', 'Z', 'Ž'],
            '_',
            $escaped
        );
    }

    /**
     * Name split into normalised, de-duplicated, sorted parts.
     *
     * Sorting makes the comparison order-independent; `Str::ascii` makes it
     * diacritic-independent.
     *
     * @return list<string>
     */
    public static function nameTokens(string $name): array
    {
        $tokens = preg_split(self::NAME_SPLIT, mb_strtolower(Str::ascii(trim($name))), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_values(array_unique($tokens));
        sort($tokens);

        return $tokens;
    }

    /**
     * Lowercase, strip diacritics, collapse whitespace and sort the name parts, so
     * "Jonaitis Jonas" and "Jonas Jonaitis" compare equal — as do "Čiurlionis" and
     * an admin who typed "Ciurlionis".
     */
    public static function normaliseName(string $name): string
    {
        return implode(' ', self::nameTokens($name));
    }

    /**
     * The part before the @, so vardas.pavarde@stud.vu.lt and vardas.pavarde@vusa.lt
     * are recognised as the same person under two addresses.
     */
    public static function emailLocalPart(string $email): string
    {
        $local = Str::before(mb_strtolower(trim($email)), '@');

        return $local === $email ? '' : $local;
    }

    /**
     * jonas.jonaitis@stud.vu.lt -> j***@stud.vu.lt
     *
     * Enough for somebody who knows the person to recognise the account; not enough
     * to collect addresses out of an endpoint that intentionally searches every user.
     */
    public static function maskEmail(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return '';
        }

        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).'***@'.$domain;
    }
}
