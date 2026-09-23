export interface CollectionQuickFilter {
  id: string;
  label: string;
  active: boolean;
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
  /** Always shown, and left out of the "Stulpeliai" menu. The first column is always pinned. */
  pinned?: boolean;
}

/** Trash is a filter over the same collection (.ai/rules/js-pages-admin.md), not its own page. */
export interface CollectionTrash {
  /** Soft-deleted records the viewer may restore; the control hides at zero. */
  count: number;
  active: boolean;
}
