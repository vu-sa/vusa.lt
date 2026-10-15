import type { VisitOptions } from '@inertiajs/core';
import { describe, expect, it } from 'vitest';

import { wantsViewTransition } from '@/Utils/viewTransition';

describe('wantsViewTransition', () => {
  it('animates a visit that swaps the page', () => {
    expect(wantsViewTransition({ method: 'get' })).toBe(true);
  });

  it('animates a <Link> click, which always sends viewTransition: false', () => {
    expect(wantsViewTransition({ method: 'get', preserveState: false, async: false, only: [], viewTransition: false })).toBe(true);
  });

  it.each<[string, VisitOptions]>([
    ['a background save', { method: 'put', async: true, preserveState: true, only: ['reservationCart'] }],
    ['a state-preserving visit', { preserveState: true }],
    ['a partial reload', { only: ['items'] }],
  ])('leaves %s alone', (_, options) => {
    expect(wantsViewTransition(options)).toBe(false);
  });
});
