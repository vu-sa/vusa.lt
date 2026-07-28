import { describe, expect, it } from 'vitest';

import { deriveBlockSummary } from '../blockSummary';
import type { ContentPart } from '../../Types';

function part<TJson = unknown, TOptions = unknown>(
  type: string,
  json_content: TJson,
  options: TOptions = {} as TOptions,
): ContentPart<TJson, TOptions> {
  return { type, json_content, options };
}

describe('deriveBlockSummary', () => {
  it('extracts the first text node from a tiptap doc', () => {
    const doc = { type: 'doc', content: [{ type: 'paragraph', content: [{ type: 'text', text: 'Hello world' }] }] };
    expect(deriveBlockSummary(part('tiptap', doc))).toBe('Hello world');
  });

  it('falls back to a dash for an empty tiptap doc', () => {
    expect(deriveBlockSummary(part('tiptap', {}))).toBe('—');
  });

  it('truncates long tiptap text', () => {
    const longText = 'x'.repeat(100);
    const doc = { type: 'doc', content: [{ type: 'paragraph', content: [{ type: 'text', text: longText }] }] };
    const summary = deriveBlockSummary(part('tiptap', doc));
    expect(summary.length).toBeLessThan(70);
    expect(summary.endsWith('…')).toBe(true);
  });

  it('reads shadcn-card title from options', () => {
    expect(deriveBlockSummary(part('shadcn-card', {}, { title: 'Svarbu žinoti' }))).toBe('Svarbu žinoti');
  });

  it('shows accordion first label plus remaining count', () => {
    const items = [{ label: 'Pirmas klausimas', content: {} }, { label: 'Antras', content: {} }];
    expect(deriveBlockSummary(part('shadcn-accordion', items))).toBe('Pirmas klausimas (+1)');
  });

  it('handles an empty accordion', () => {
    expect(deriveBlockSummary(part('shadcn-accordion', []))).toBe('—');
  });

  it('strips HTML from the hero title', () => {
    expect(deriveBlockSummary(part('hero', { title: '<p>Prisijunk <b>dabar</b></p>' }))).toBe('Prisijunk dabar');
  });

  it('decodes HTML entities while stripping tags', () => {
    expect(deriveBlockSummary(part('hero', { title: '<p>&lt;Sveikas&gt; &amp; &quot;pasaulis&quot;</p>' }))).toBe('<Sveikas> & "pasaulis"');
  });

  it('reads a plain title for news/calendar', () => {
    expect(deriveBlockSummary(part('news', { title: 'Naujienos blokas' }))).toBe('Naujienos blokas');
    expect(deriveBlockSummary(part('calendar', { title: 'Renginiai' }))).toBe('Renginiai');
  });

  it('extracts a hostname for embeds', () => {
    expect(deriveBlockSummary(part('spotify-embed', { url: 'https://open.spotify.com/track/abc' }))).toBe('open.spotify.com');
    expect(deriveBlockSummary(part('social-embed', { url: 'https://www.facebook.com/post/1' }))).toBe('facebook.com');
  });

  it('falls back to the raw string when the embed url is not a valid URL', () => {
    expect(deriveBlockSummary(part('spotify-embed', { url: 'not-a-url' }))).toBe('not-a-url');
  });

  it('reads the flow-graph preset name', () => {
    expect(deriveBlockSummary(part('flow-graph', { preset: 'VusaStructure' }))).toBe('VusaStructure');
  });

  it('reads number-stat-section title from options', () => {
    expect(deriveBlockSummary(part('number-stat-section', [], { title: 'VU SA skaičiais' }))).toBe('VU SA skaičiais');
  });

  it('prefers the lt title for text-box, falling back to en', () => {
    expect(deriveBlockSummary(part('text-box', {}, { title: { lt: 'Pasiūlymai', en: 'Suggestions' } }))).toBe('Pasiūlymai');
    expect(deriveBlockSummary(part('text-box', {}, { title: { lt: '', en: 'Suggestions' } }))).toBe('Suggestions');
  });

  it('shows carousel/card-stack first item title plus remaining count', () => {
    const slides = [{ title: 'Bendruomenė' }, { title: 'Poveikis' }, { title: 'Augimas' }];
    expect(deriveBlockSummary(part('carousel-slide-deck', slides))).toBe('Bendruomenė (+2)');

    const cards = [{ title: 'Studijos' }];
    expect(deriveBlockSummary(part('card-stack', cards))).toBe('Studijos');
  });

  it('returns a dash for an unknown type instead of throwing', () => {
    expect(deriveBlockSummary(part('does-not-exist', {}))).toBe('—');
  });

  // $t/$tChoice are globally mocked to return the key verbatim (tests/setup.ts), so these
  // only assert the pluralized-count paths resolve without throwing, not the real Lithuanian text.
  it('resolves the pluralized image count without throwing', () => {
    expect(() => deriveBlockSummary(part('image-grid', [{ image: 'a.jpg' }, { image: 'b.jpg' }]))).not.toThrow();
    expect(() => deriveBlockSummary(part('photo-gallery', []))).not.toThrow();
  });

  it('resolves the pluralized row count for content-grid without throwing', () => {
    expect(() => deriveBlockSummary(part('content-grid', [{ columns: [] }]))).not.toThrow();
  });
});
