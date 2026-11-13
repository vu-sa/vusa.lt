import type { WikiPageSummary } from '@/Types/learning';

/** Lowercase without diacritics, so "skvc", "Ž" and "z" all find what people mean. */
export const foldText = (value: string): string => value.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLocaleLowerCase('lt');

/** Abbreviation and title matches first, then summary matches; keeps the input order within a rank. */
export function searchWiki<T extends WikiPageSummary>(pages: T[], query: string): T[] {
  const needle = foldText(query.trim());
  if (!needle) return pages;

  const rank = (page: T): number => {
    const abbreviation = foldText(page.abbreviation);
    const title = foldText(page.title);
    if (abbreviation === needle) return 0;
    if (title.startsWith(needle) || abbreviation.startsWith(needle)) return 1;
    if (title.includes(needle)) return 2;
    if (foldText(page.summary).includes(needle)) return 3;
    return -1;
  };

  return pages
    .map((page, index) => ({ page, index, rank: rank(page) }))
    .filter(entry => entry.rank >= 0)
    .sort((a, b) => a.rank - b.rank || a.index - b.index)
    .map(entry => entry.page);
}
