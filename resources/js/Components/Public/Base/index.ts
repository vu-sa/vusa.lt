/**
 * Public design-system primitives.
 *
 * The public counterpart to `Components/Patterns/` (which is admin-only by construction — see
 * its barrel). These carry the editorial language of the public site: sharp corners, hairline
 * rules, one brand accent per view, generous whitespace.
 *
 * Rules for anything added here:
 * - Domain-free. No `route()`, no `App.Entities.*` types — pass a resolved `href` string in.
 * - Colour comes from tokens (`bg-card`, `text-muted-foreground`, `border-border`, `text-brand`),
 *   never raw `zinc-*`/`bg-white`. Tokens are what make the palette swap per surface.
 * - `--brand`, not `--accent`: `--accent` is shadcn's hover surface.
 * - No `rounded-*`. The public surface zeroes the radius scale, but `rounded-full` is a literal
 *   and would survive.
 * - No `ui/switch`. A switch is a pill built from `rounded-full`, so it renders as the one soft
 *   shape on a cornery page. Use `CheckControl` for any on/off setting here.
 * - Every primitive ships a story covering its variants in both themes; that is the catalogue.
 *
 * See `resources/js/Components/CLAUDE.md`.
 */

export { default as AccessibilityMenu } from './AccessibilityMenu.vue';
export { default as CheckControl } from './CheckControl.vue';
export { default as DatePlate } from './DatePlate.vue';
export { default as DisplayHeading } from './DisplayHeading.vue';
export { default as EyebrowLabel } from './EyebrowLabel.vue';
export { default as HairlineList } from './HairlineList.vue';
export { default as HairlineRow } from './HairlineRow.vue';
export { default as HeaderWordmark } from './HeaderWordmark.vue';
export { default as MediaFrame } from './MediaFrame.vue';
export { default as PageTitleBand } from './PageTitleBand.vue';
export { default as SectionBand } from './SectionBand.vue';
export { default as StatCell } from './StatCell.vue';
export { default as TagChip } from './TagChip.vue';
