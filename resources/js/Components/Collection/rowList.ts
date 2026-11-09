/** The hairline list every rows view and record-section list share, so the two read as one vocabulary. */
export const ROW_LIST_CLASS = 'divide-y divide-border/60 border-y border-border';
/**
 * Directly under a section header: its rule is the box's top edge, so one row never sits between three
 * lines. `-mt-3` closes `OverviewSection`'s gap so the sides meet that rule.
 */
export const ROW_LIST_BOXED_CLASS = '-mt-3 divide-y divide-border/60 border-x border-b border-border';
/** A boxed list under a subheading, which draws no rule of its own. */
export const ROW_LIST_BOX_CLASS = 'divide-y divide-border/60 border border-border';
export const ROW_ITEM_CLASS = 'flex items-stretch transition-colors hover:bg-secondary/40';
/** Padding for a `CollectionRow` placed in a row list item. */
export const ROW_PADDING_CLASS = 'px-3 py-2.5 sm:px-4';
