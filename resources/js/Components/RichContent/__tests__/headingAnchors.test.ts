import { describe, expect, it } from 'vitest';
import { collectHeadingIds, normalizeHeadingAnchors } from '../headingAnchors';
import { extractAnchorLinks } from '../tocAnchors';

describe('document heading anchors', () => {
  it('keeps valid anchors and makes duplicates unique across blocks', () => {
    const heading = (id?: string) => ({ type: 'heading', attrs: { level: 2, id }, content: [{ type: 'text', text: 'Įvadas ' }, { type: 'text', text: 'ir informacija', marks: [{ type: 'bold' }] }] });
    const parts = [
      { type: 'tiptap', json_content: { content: [heading('existing')] } },
      { type: 'tiptap', json_content: { content: [heading('existing'), heading()] } },
    ];
    normalizeHeadingAnchors(parts);
    expect(extractAnchorLinks(parts).map(link => link.href)).toEqual(['#existing', '#existing-1', '#ivadas-ir-informacija']);
    expect(extractAnchorLinks(parts).map(link => link.title)).toEqual(['Įvadas ir informacija', 'Įvadas ir informacija', 'Įvadas ir informacija']);
    const previous = JSON.stringify(parts);
    normalizeHeadingAnchors(parts);
    expect(JSON.stringify(parts)).toBe(previous);
  });

  it('keeps a loaded anchor through a retitle while a heading typed this session follows its text', () => {
    const heading = (text: string, id?: string) => ({ type: 'heading', attrs: { level: 2, id }, content: [{ type: 'text', text }] });
    const parts = [{ type: 'tiptap', json_content: { content: [heading('Kontaktai', 'kontaktai'), heading('N')] } }];
    const locked = collectHeadingIds(parts);
    normalizeHeadingAnchors(parts, locked);
    parts[0].json_content.content[0].content[0].text = 'Susisiek';
    parts[0].json_content.content[1].content[0].text = 'Nauja tema';
    normalizeHeadingAnchors(parts, locked);
    expect(extractAnchorLinks(parts).map(link => link.href)).toEqual(['#kontaktai', '#nauja-tema']);
  });
});
