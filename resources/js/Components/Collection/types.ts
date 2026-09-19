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
}
