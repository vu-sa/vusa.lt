/**
 * "Smart" responsive grid-column classes driven by an item count, instead of a fixed
 * `sm:grid-cols-2 lg:grid-cols-3` that leaves a single stretched card (1 item) or an
 * unbalanced 2-then-1 row (2 items) — link-list and grouped event-list cards both had
 * this complaint.
 */
export function smartGridCols(count: number): string {
  if (count <= 1) return 'grid-cols-1';
  if (count === 2) return 'grid-cols-1 sm:grid-cols-2';
  return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
}
