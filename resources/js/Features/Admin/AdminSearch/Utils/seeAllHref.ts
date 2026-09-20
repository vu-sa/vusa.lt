export interface SearchDestination {
  /** Null when the user may not open the full list; the group then shows no link. */
  href: string | null;
  /** Typesense collection pages read `q`, database table pages read `search`. */
  queryKey: string;
}

/** Where "Rodyti visus" leads, carrying the query in the key that destination actually reads. */
export function seeAllHref(destination: SearchDestination | undefined, query: string): string | null {
  if (!destination?.href) {
    return null;
  }

  const trimmed = query.trim();
  if (trimmed === '') {
    return destination.href;
  }

  const separator = destination.href.includes('?') ? '&' : '?';

  return `${destination.href}${separator}${destination.queryKey}=${encodeURIComponent(trimmed)}`;
}
