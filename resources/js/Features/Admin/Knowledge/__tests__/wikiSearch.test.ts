import { describe, expect, it } from 'vitest';

import { groupByLetter } from '../wikiAlphabet';
import { searchWiki } from '../wikiSearch';

const page = (id: string, title: string, abbreviation = '', summary = '') => ({ id, title, abbreviation, summary, url: `/${id}`, sectionId: null, updatedAt: null });

describe('wiki search and alphabet', () => {
  it('ranks an exact abbreviation, then title starts, then title and summary matches, ignoring diacritics', () => {
    const pages = [
      page('summary', 'Akreditacija', '', 'SKVC vertina'),
      page('contains', 'Apie skvc darbą'),
      page('skvc', 'Studijų kokybės vertinimo centras', 'SKVC'),
      page('starts', 'Skvc istorija'),
    ];
    expect(searchWiki(pages, 'skvc').map(result => result.id)).toEqual(['skvc', 'starts', 'contains', 'summary']);
    expect(searchWiki(pages, 'studiju').map(result => result.id)).toEqual(['skvc']);
    expect(searchWiki(pages, '  ')).toHaveLength(4);
  });

  it('cuts the collated index at each first letter, keeping Lithuanian letters apart', () => {
    const groups = groupByLetter([page('a', 'Akademija'), page('c', 'Čempionas'), page('c2', 'čekis'), page('n', '5 faktai'), page('z', 'Žodis')]);
    expect(groups.map(group => [group.letter, group.pages.length])).toEqual([['A', 1], ['Č', 2], ['#', 1], ['Ž', 1]]);
  });
});
