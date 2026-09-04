/**
 * Resolves a block's *chrome* — whether it renders as a flow block (no ground, sits in
 * the content column) or a full-bleed "band" with an automatically-alternating tint —
 * the chrome analogue of `blockLayout.ts`, which resolves *measure*. Replaces the old
 * per-block `background`/`padding`/`rounded`/`divider`/`bleed` authoring surface: those
 * let every block opt independently out of the page's rhythm, so alternation is now
 * computed from document order. Authors can opt into a plain ground and choose only
 * that plain block's vertical padding.
 */
import { getContentType } from './Types';
import { BAND_GROUND_CLASS, BAND_PADDING, BAND_PADDING_COMPACT, PLAIN_PADDING_CLASS } from './sectionClasses';
import type { BandTint, PlainPadding } from './sectionClasses';
import type { BlockWidth } from './Types';

export type { BandTint };
export type BlockPresentation = 'auto' | 'plain';

export interface LayoutableElement {
  type: string;
  options?: Record<string, unknown> | null;
}

export interface BandResolution {
  /** false → renders in the flow, no chrome at all (today's plain block). */
  isBand: boolean;
  tint: BandTint | null;
  /** True iff the block resolved to the `full` canvas column — a band that doesn't
   *  reach the viewport edge is a contained, bordered panel instead (see `classes`). */
  bleeds: boolean;
  /** Ready-to-bind class list for the band's root element. Includes `.rc-band`. */
  classes: string[];
}

const FLOW: BandResolution = { isBand: false, tint: null, bleeds: false, classes: [] };

/** Whether this type can ever render as a band, independent of any specific block's options. */
export function resolveBandRole(type: string, options?: Record<string, unknown> | null): 'flow' | 'band' {
  const role = getContentType(type).bandRole;
  if (!role) return 'flow';
  return typeof role === 'function' ? role(options) : role;
}

/**
 * Resolves one block's band chrome. `slot` is this block's position among the page's
 * *other* bands (see `resolveBands`) — pass `0` for a standalone preview (picker,
 * single-block editor) where there is no surrounding document to alternate against.
 */
export function resolveBand(element: LayoutableElement, slot: number): BandResolution {
  if (resolveBandRole(element.type, element.options) === 'flow') return FLOW;

  const presentation = (element.options?.presentation as BlockPresentation | undefined) ?? 'auto';
  if (presentation === 'plain') {
    const padding = (element.options?.plainPadding as PlainPadding | undefined) ?? 'default';
    const paddingClass = PLAIN_PADDING_CLASS[padding];

    return { ...FLOW, classes: paddingClass ? [paddingClass] : [] };
  }

  const contentType = getContentType(element.type);
  const width = (element.options?.width as BlockWidth | undefined) ?? contentType.defaultWidth;
  const bleeds = width === 'full';

  // cta-band is always the one loud band a page is allowed — see its own docblock —
  // regardless of what options.presentation says (it has no presentation control at all).
  const tint: BandTint = element.type === 'cta-band'
    ? 'emphasis'
    : (slot % 2 === 0 ? 'canvas' : 'tint');

  const classes = [
    'rc-band',
    'relative',
    'scroll-mt-32',
    BAND_PADDING,
    BAND_GROUND_CLASS[tint],
    bleeds ? 'rc-viewport' : 'rounded-xl border border-border overflow-hidden',
  ];

  return { isBand: true, tint, bleeds, classes };
}

/**
 * Swaps the standard band padding for the compact one — `hero`'s `banner` variant is a
 * single-row strip and the flattened `BAND_PADDING` would look wrong on it. The one
 * legitimate per-block padding escape, and it's internal (not authorable): HeroElement
 * calls this itself rather than bandLayout picking favourites among block types.
 */
export function withCompactPadding(band: BandResolution): BandResolution {
  if (!band.isBand) return band;
  return { ...band, classes: band.classes.map(c => (c === BAND_PADDING ? BAND_PADDING_COMPACT : c)) };
}

/**
 * Resolves every part's band chrome in one pass, threading a running "how many bands
 * deep" counter through the document so tints alternate. Rules, in order:
 * - A `flow`-role type never consumes a slot (it was never in the running).
 * - `presentation: 'plain'` forces flow and does not consume a slot either — a block
 *   opting out must not shift its neighbours' tints.
 * - `cta-band` consumes a slot like every other band, but always uses its fixed emphasis tint.
 * - Every part following a `section` marker (until the next one, or `wraps: 'none'`
 *   ends the wrapping early) is forced flow — a band nested inside a band's own section
 *   canvas is incoherent.
 */
export function resolveBands(parts: readonly LayoutableElement[]): Map<LayoutableElement, BandResolution> {
  const map = new Map<LayoutableElement, BandResolution>();
  let slot = 0;
  let insideSection = false;

  for (const part of parts) {
    if (part.type === 'section') {
      const resolved = resolveBand(part, slot);
      map.set(part, resolved);
      if (resolved.isBand) slot++;
      insideSection = (part.options as { wraps?: string } | null | undefined)?.wraps !== 'none';
      continue;
    }

    if (insideSection) {
      map.set(part, FLOW);
      continue;
    }

    const resolved = resolveBand(part, slot);
    map.set(part, resolved);
    if (resolved.isBand) slot++;
  }

  return map;
}
