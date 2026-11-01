export interface CollectionQuickFilter {
  id: string;
  label: string;
  active: boolean;
  spotlight?: { id: string; title: string; description: string };
}

/** A column of the table view. Cells render through `CollectionPage`'s `cell` slot. */
export interface CollectionColumn {
  key: string;
  label: string;
  class?: string;
  /**
   * The source field this column sorts by. The header becomes a sort toggle when the source
   * offers both `field:asc` and `field:desc` — a Typesense collection only sorts on fields it
   * indexed as sortable, so the source, not the page, decides.
   */
  sortField?: string;
  /** Always shown, and left out of the "Stulpeliai" menu. Does not control sticky positioning; the first column is always shown. */
  pinned?: boolean;
  /** Starts off; "Stulpeliai" can turn it on, and that choice is remembered. */
  defaultHidden?: boolean;
}

/** Trash is a filter over the same collection (.ai/rules/js-pages-admin.md), not its own page. */
export interface CollectionTrash {
  /** Soft-deleted records the viewer may restore; the control hides at zero. */
  count: number;
  active: boolean;
}
