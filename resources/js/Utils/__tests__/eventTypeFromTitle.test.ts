import { describe, expect, it } from 'vitest';

import { guessEventTypeSlug } from '../eventTypeFromTitle';

describe('guessEventTypeSlug', () => {
  it.each([
    ['Pirmakursių stovykla', 'stovykla'],
    ['Senato komisijos konferencija', 'konferencija'],
    ['Ataskaitinė-rinkiminė konferencija', 'konferencija'],
    ['Studijų programos komiteto posėdis', 'posedis'],
    ['Kandidatų debatai', 'rinkimai'],
    ['Leadership workshop', 'mokymai'],
    ['Susitikimas su rektoratu', 'atstovavimas'],
    ['Paraiškų teikimo terminas', 'terminas'],
  ])('matches "%s" to %s', (title, slug) => {
    expect(guessEventTypeSlug([title])).toBe(slug);
  });

  it('matches the English title when the Lithuanian one says nothing', () => {
    expect(guessEventTypeSlug(['Renginys', 'Summer camp'])).toBe('stovykla');
  });

  it('prefers an explicit susirinkimas over a linked meeting', () => {
    expect(guessEventTypeSlug(['Visuotinis susirinkimas'], true)).toBe('susirinkimas');
    expect(guessEventTypeSlug(['Renginys'], true)).toBe('posedis');
  });

  it('leaves an unrecognised title untyped', () => {
    expect(guessEventTypeSlug(['Kalėdinis vakarėlis', ''])).toBeNull();
  });
});
