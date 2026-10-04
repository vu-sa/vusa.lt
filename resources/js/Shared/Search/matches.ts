export interface SearchMatch {
  field: string;
  segments: Array<{ text: string; matched: boolean }>;
}

export interface HighlightedHit {
  document: Record<string, unknown>;
  highlights?: Array<{ field?: string; snippet?: string; snippets?: string[] }>;
}

export interface SearchMatchDocument {
  _searchMatch?: SearchMatch;
  _searchTitleMatches?: SearchMatch[];
}

const identityFields = new Set(['title', 'title_lt', 'title_en', 'name', 'name_lt', 'name_en']);

function plainSnippet(value: string): string {
  let text = value.replace(/<mark>/g, '⟦').replace(/<\/mark>/g, '⟧');
  for (let pass = 0; pass < 3 && /[<&]/.test(text); pass++) {
    const template = document.createElement('template');
    template.innerHTML = text.replace(/<\/?(?:p|div|br|li|h[1-6])\b[^>]*>/gi, ' ');
    template.content.querySelectorAll('script,style').forEach(element => element.remove());
    text = template.content.textContent ?? '';
  }
  return text.replace(/\s+/g, ' ').trim();
}

function segmentsFromSnippet(text: string): SearchMatch['segments'] {
  const segments: SearchMatch['segments'] = [];
  let matched = false;
  for (const part of text.split(/(⟦|⟧)/g)) {
    if (part === '⟦') matched = true;
    else if (part === '⟧') matched = false;
    else if (part) segments.push({ text: part, matched });
  }
  return segments;
}

export function matchTitle(title: string, matches?: SearchMatch[]): SearchMatch | undefined {
  return matches?.find(match => match.segments.map(segment => segment.text).join('') === title);
}

export function sliceMatch(match: SearchMatch | undefined, start: number, end: number): SearchMatch | undefined {
  if (!match) return undefined;
  let offset = 0;
  return { field: match.field, segments: match.segments.flatMap((segment) => {
    const segmentStart = offset;
    offset += segment.text.length;
    const text = segment.text.slice(Math.max(0, start - segmentStart), Math.max(0, Math.min(segment.text.length, end - segmentStart)));
    return text ? [{ text, matched: segment.matched }] : [];
  }) };
}

function fullTitleMatch(title: string, snippet: SearchMatch): SearchMatch {
  const terms = snippet.segments.filter(segment => segment.matched).map(segment => segment.text.toLocaleLowerCase());
  const folded = title.toLocaleLowerCase();
  const matched = Array.from({ length: title.length }, () => false);
  for (const term of terms) {
    if (!term) continue;
    let start = folded.indexOf(term);
    while (start !== -1) {
      matched.fill(true, start, start + term.length);
      start = folded.indexOf(term, start + term.length);
    }
  }
  const segments: SearchMatch['segments'] = [];
  for (let index = 0; index < title.length; index++) {
    const previous = segments.at(-1);
    if (previous && previous.matched === matched[index]) previous.text += title[index];
    else segments.push({ text: title[index], matched: matched[index] });
  }
  return { field: snippet.field, segments };
}

export function documentWithMatch(hit: HighlightedHit): Record<string, unknown> {
  const titleMatches = (hit.highlights ?? []).filter(item => item.field && (identityFields.has(item.field) || item.field.startsWith('search_text_'))).flatMap((item) => {
    return (item.snippet ? [item.snippet] : item.snippets ?? []).map((snippet) => {
      const match = { field: item.field!, segments: segmentsFromSnippet(plainSnippet(snippet)) };
      const value = hit.document[item.field!];
      return identityFields.has(item.field!) && typeof value === 'string' ? fullTitleMatch(value, match) : match;
    }).filter(match => match.segments.some(segment => segment.matched));
  });
  for (const match of [...titleMatches].filter(item => item.field.startsWith('search_text_'))) {
    for (const field of identityFields) {
      const value = hit.document[field];
      if (typeof value !== 'string') continue;
      const titleMatch = fullTitleMatch(value, match);
      if (titleMatch.segments.some(segment => segment.matched)) titleMatches.push(titleMatch);
    }
  }
  const document = titleMatches.length ? { ...hit.document, _searchTitleMatches: titleMatches } : hit.document;
  const titles = [...identityFields].map(field => hit.document[field]).filter((value): value is string => typeof value === 'string').map(value => plainSnippet(value).toLocaleLowerCase());
  const highlights = (hit.highlights ?? []).filter(item => item.field && !identityFields.has(item.field) && !item.field.startsWith('search_text_'));
  highlights.sort((a, b) => Number(b.field === 'body') - Number(a.field === 'body'));
  const candidates = highlights.flatMap(item => (item.snippet ? [item.snippet] : item.snippets ?? []).map(snippet => ({ field: item.field, text: plainSnippet(snippet) })));
  const highlight = candidates.find(item => item.text.includes('⟦') && !titles.includes(item.text.replace(/[⟦⟧]/g, '').toLocaleLowerCase()));
  if (!highlight?.field) return document;
  return { ...document, _searchMatch: { field: highlight.field, segments: segmentsFromSnippet(highlight.text) } satisfies SearchMatch };
}
