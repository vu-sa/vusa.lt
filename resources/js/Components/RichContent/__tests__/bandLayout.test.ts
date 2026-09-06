import { describe, expect, it } from 'vitest';

import { resolveBand, resolveBands } from '../bandLayout';

describe('rich-content band layout', () => {
  it('keeps automatic sections on the default band padding', () => {
    const band = resolveBand({ type: 'content-grid', options: { presentation: 'auto' } }, 0);

    expect(band.isBand).toBe(true);
    expect(band.classes).toContain('py-16');
  });

  it('uses the default padding when a plain section has no explicit padding choice', () => {
    const band = resolveBand({ type: 'content-grid', options: { presentation: 'plain' } }, 0);

    expect(band.isBand).toBe(false);
    expect(band.classes).toContain('py-16');
  });

  it.each([
    ['none', undefined],
    ['compact', 'py-8'],
    ['default', 'py-16'],
  ] as const)('applies the %s padding choice to plain sections', (plainPadding, expectedClass) => {
    const band = resolveBand({ type: 'content-grid', options: { presentation: 'plain', plainPadding } }, 0);

    expect(band.isBand).toBe(false);
    if (expectedClass) expect(band.classes).toContain(expectedClass);
    else expect(band.classes).toEqual([]);
  });

  it('keeps a self-spaced band outside a preceding section', () => {
    const section = { type: 'section', options: { wraps: 'following' } };
    const grid = { type: 'content-grid', options: { presentation: 'plain', plainPadding: 'none' } };
    const cardStack = { type: 'card-stack', options: { presentation: 'plain' } };

    const bands = resolveBands([section, grid, cardStack]);

    expect(bands.get(grid)?.isSectionChild).toBe(true);
    expect(bands.get(grid)?.classes).toEqual([]);
    expect(bands.get(cardStack)?.isSectionChild).toBe(false);
    expect(bands.get(cardStack)?.classes).toContain('py-16');
  });
});
