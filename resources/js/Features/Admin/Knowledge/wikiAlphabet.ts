import type { WikiIndexEntry } from '@/Types/learning';

export interface WikiLetterGroup {
  letter: string;
  pages: WikiIndexEntry[];
}

/** The index arrives collated by the server; this only cuts it at each new first letter. */
export function groupByLetter(pages: WikiIndexEntry[], locale = 'lt'): WikiLetterGroup[] {
  const groups: WikiLetterGroup[] = [];
  for (const page of pages) {
    const first = page.title.trim().charAt(0).toLocaleUpperCase(locale);
    const letter = /\p{L}/u.test(first) ? first : '#';
    const last = groups.at(-1);
    if (last?.letter === letter) last.pages.push(page);
    else groups.push({ letter, pages: [page] });
  }

  return groups;
}

export const letterAnchor = (letter: string): string => `letter-${letter === '#' ? 'other' : letter.toLocaleLowerCase('lt')}`;
