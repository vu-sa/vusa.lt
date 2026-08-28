import type { ParsedCadence, ParsedRow, TimelineScope } from './types';

/**
 * Which terms a row is measured against.
 *
 * Mirrors `App\Support\Dutiables\CadenceMatcher::applicable`: an institution that defines
 * even one cadence of its own never falls back to the global ladder. See .ai/rules/cadences.md.
 */
export function applicableCadences(
  cadences: ParsedCadence[],
  institutionId: string | null,
): ParsedCadence[] {
  const scoped = institutionId === null
    ? []
    : cadences.filter(cadence => cadence.institution_id === institutionId);

  return scoped.length > 0 ? scoped : cadences.filter(cadence => cadence.institution_id === null);
}

/**
 * The single ladder the chart draws behind the bars.
 *
 * The payload carries the global ladder *and* every override in play, because per-row
 * matching needs both. Drawing all of them stacks two translucent greens over the whole
 * domain, which is how a term boundary stops being visible at all — the bands must be one
 * ruler or they are not a ruler.
 *
 * A user-scoped chart can genuinely span institutions on different ladders; there the
 * global one is the only ruler every row shares.
 */
export function bandLadder(
  cadences: ParsedCadence[],
  scope: TimelineScope | null,
  rows: ParsedRow[],
): ParsedCadence[] {
  if (scope?.institution_id) return applicableCadences(cadences, scope.institution_id);

  const institutionIds = new Set(rows.map(row => row.institution_id));

  if (institutionIds.size === 1) {
    return applicableCadences(cadences, [...institutionIds][0]);
  }

  return cadences.filter(cadence => cadence.is_global);
}
