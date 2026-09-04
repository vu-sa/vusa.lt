import { describe, expect, it } from 'vitest';

import { resolveBand } from '../bandLayout';

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
});
