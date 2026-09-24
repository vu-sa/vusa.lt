/**
 * Brand primitives shared by the public site and the admin: the ruled display headline, its
 * eyebrow, square tag chip, and ruled grid. Both surfaces use one visual language, so these live
 * below `Public/Base/` and `Patterns/` and either may import them.
 *
 * Same rules as `Public/Base/`: domain-free, token colours only, no `rounded-*`, a story each.
 */

export { default as DisplayHeading } from './DisplayHeading.vue';
export { default as EyebrowLabel } from './EyebrowLabel.vue';
export { default as TagChip } from './TagChip.vue';
export { default as MediaFrame } from './MediaFrame.vue';
export { default as RuledGrid } from './RuledGrid.vue';
export type { RuledGridColumns } from './RuledGrid.vue';
