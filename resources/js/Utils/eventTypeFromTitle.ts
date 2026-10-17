/**
 * Ordered slug → pattern rules; first match wins. `konferencija` must precede `atstovavimas`:
 * a conference title routinely also contains "senat" or "komisij".
 */
const TITLE_RULES: ReadonlyArray<readonly [string, RegExp]> = [
  ['stovykla', /stovykl|camp/iu],
  ['konferencija', /konferencij|ataskaitin.{0,3}-rinkimin/iu],
  ['posedis', /posėd/iu],
  ['rinkimai', /rinkim|kandidat|balsav/iu],
  ['mokymai', /mokym|seminar|dirbtuv|workshop|training/iu],
  ['atstovavimas', /susitik|dalyvau|darbo grup|rektorat|senat|komisij/iu],
  ['terminas', /registracij|terminas|paraišk|deadline/iu],
];

/** An explicit susirinkimas title is more specific than the linked-meeting signal. */
export function guessEventTypeSlug(titles: Array<string | null | undefined>, hasMeeting = false): string | null {
  const haystack = titles.filter(Boolean).join('\n');

  if (/susirinkim/iu.test(haystack)) {
    return 'susirinkimas';
  }

  if (hasMeeting) {
    return 'posedis';
  }

  return TITLE_RULES.find(([, pattern]) => pattern.test(haystack))?.[0] ?? null;
}
