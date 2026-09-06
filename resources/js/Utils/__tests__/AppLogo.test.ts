import { describe, expect, it } from 'vitest';

import { getAppLogoSrc } from '../AppLogo';

describe('getAppLogoSrc', () => {
  it('returns the matching tenant logo in the requested language', () => {
    expect(getAppLogoSrc('mif', 'en')).toBe('/logos/hor/en/vusamif.lin.hor.tams.svg');
  });

  it('uses the supplied exceptional tenant filenames', () => {
    expect(getAppLogoSrc('chgf', 'lt')).toBe('/logos/hor/lt/vusachgf.lin.hor.balt.svg');
    expect(getAppLogoSrc('sa', 'en')).toBe('/logos/hor/en/vusasa.lin.hor.tams.en.svg');
  });

  it('falls back to the Lithuanian central logo for unsupported aliases and locales', () => {
    expect(getAppLogoSrc('astronomu-klubas', 'de')).toBe('/logos/hor/lt/vusa.lin.hor.tams.svg');
  });
});
