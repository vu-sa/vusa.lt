import { describe, expect, it } from 'vitest';

import { extractAnchorLinks } from '../tocAnchors';

function tiptapHeading(level: 2 | 3, id: string, text: string) {
  return { type: 'heading' as const, attrs: { level, id }, content: [{ text }] };
}

describe('extractAnchorLinks', () => {
  it('returns an empty array for no parts', () => {
    expect(extractAnchorLinks(undefined)).toEqual([]);
    expect(extractAnchorLinks(null)).toEqual([]);
    expect(extractAnchorLinks([])).toEqual([]);
  });

  it('extracts h2 headings from a tiptap block as top-level entries', () => {
    const parts = [
      { id: 1, type: 'tiptap', json_content: { content: [tiptapHeading(2, 'intro', 'Intro'), tiptapHeading(2, 'more', 'More')] } },
    ];
    expect(extractAnchorLinks(parts)).toEqual([
      { title: 'Intro', href: '#intro', children: [] },
      { title: 'More', href: '#more', children: [] },
    ]);
  });

  it('nests h3 headings under the preceding h2', () => {
    const parts = [
      { id: 1, type: 'tiptap', json_content: { content: [tiptapHeading(2, 'a', 'A'), tiptapHeading(3, 'a-1', 'A.1')] } },
    ];
    expect(extractAnchorLinks(parts)).toEqual([
      { title: 'A', href: '#a', children: [{ title: 'A.1', href: '#a-1' }] },
    ]);
  });

  it('promotes an h3 with no preceding h2 to a top-level entry', () => {
    const parts = [{ id: 1, type: 'tiptap', json_content: { content: [tiptapHeading(3, 'orphan', 'Orphan')] } }];
    expect(extractAnchorLinks(parts)).toEqual([{ title: 'Orphan', href: '#orphan', children: [] }]);
  });

  it('ignores tiptap blocks with no headings', () => {
    const parts = [{ id: 1, type: 'tiptap', json_content: { content: [{ type: 'paragraph' }] } }];
    expect(extractAnchorLinks(parts)).toEqual([]);
  });

  it('contributes a top-level entry for a section-titled block with a non-empty options.title', () => {
    const parts = [{ id: 42, type: 'hero', options: { title: 'Prisijunk' } }];
    expect(extractAnchorLinks(parts)).toEqual([{ title: 'Prisijunk', href: '#rc-42', children: [] }]);
  });

  it.each(['hero', 'shadcn-accordion', 'card-stack', 'carousel-slide-deck', 'photo-gallery', 'number-stat-section'])(
    'recognises %s as a section-titled type',
    (type) => {
      const parts = [{ id: 7, type, options: { title: 'Title' } }];
      expect(extractAnchorLinks(parts)).toEqual([{ title: 'Title', href: '#rc-7', children: [] }]);
    },
  );

  it('skips a section-titled block with an empty or missing title', () => {
    expect(extractAnchorLinks([{ id: 1, type: 'hero', options: { title: '' } }])).toEqual([]);
    expect(extractAnchorLinks([{ id: 1, type: 'hero', options: {} }])).toEqual([]);
    expect(extractAnchorLinks([{ id: 1, type: 'hero' }])).toEqual([]);
  });

  it('skips types that never render a section header (image-grid, news, calendar)', () => {
    const parts = [{ id: 1, type: 'image-grid', options: { title: 'Ignored' } }];
    expect(extractAnchorLinks(parts)).toEqual([]);
  });

  it('skips a section-titled block with no id (would produce a useless #rc-undefined anchor)', () => {
    expect(extractAnchorLinks([{ type: 'hero', options: { title: 'No id' } }])).toEqual([]);
  });

  it('preserves document order across mixed tiptap and section blocks', () => {
    const parts = [
      { id: 1, type: 'tiptap', json_content: { content: [tiptapHeading(2, 'first', 'First')] } },
      { id: 2, type: 'hero', options: { title: 'Second' } },
      { id: 3, type: 'tiptap', json_content: { content: [tiptapHeading(2, 'third', 'Third')] } },
    ];
    expect(extractAnchorLinks(parts).map(l => l.title)).toEqual(['First', 'Second', 'Third']);
  });
});
