import { ATTACH_TO_SECTION_RULE_CLASS } from '@/Components/Patterns/sectionSpacing';

/** The hairline list every rows view and record-section list share, so the two read as one vocabulary. */
export const ROW_LIST_CLASS = 'divide-y divide-border/60 border-y border-border';
/** Directly under a section header: its rule is the box's top edge, so one row never sits between three lines. */
export const ROW_LIST_BOXED_CLASS = `${ATTACH_TO_SECTION_RULE_CLASS} divide-y divide-border/60 border-x border-b border-border`;
/** A boxed list under a subheading, which draws no rule of its own. */
export const ROW_LIST_BOX_CLASS = 'divide-y divide-border/60 border border-border';
export const ROW_ITEM_CLASS = 'flex items-stretch transition-colors hover:bg-secondary/40';
/** Padding for a `CollectionRow` placed in a row list item. */
export const ROW_PADDING_CLASS = 'px-3 py-2.5 sm:px-4';

/** How a short list sits in a record section: ruled like a rows view, a box, or a box under its section header's rule. */
export type RowListFrame = 'rules' | 'box' | 'attached';

export const rowListClass = (frame: RowListFrame = 'rules'): string => ({
  rules: ROW_LIST_CLASS,
  box: ROW_LIST_BOX_CLASS,
  attached: ROW_LIST_BOXED_CLASS,
})[frame];
