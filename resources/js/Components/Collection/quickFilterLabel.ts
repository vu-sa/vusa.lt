/** A count only when there is something to count, so an empty filter reads as a plain word. */
export const quickFilterLabel = (label: string, count: number): string => (count > 0 ? `${label} · ${count}` : label);
