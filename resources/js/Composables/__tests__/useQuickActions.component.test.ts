import { describe, expect, it, vi } from 'vitest';

import { QUICK_ACTION_KEYS } from '../useUIPreferences';

vi.mock('@inertiajs/vue3', () => ({
  usePage: vi.fn(() => ({ props: {} })),
  router: { visit: vi.fn() },
}));

const { QUICK_ACTION_META } = await import('../useQuickActions');

describe('QUICK_ACTION_META', () => {
  /**
   * NavQuickActions filters twice — by permission, then by preference — and an action whose
   * key is missing from QUICK_ACTION_KEYS defaults to *hidden* in the settings popover while
   * still rendering. It fails silently, so it is asserted here rather than noticed later.
   * The PHP half of the same pair is covered in HasUIPreferences' own test.
   */
  it('declares every action in the toggleable preference list', () => {
    const declared = QUICK_ACTION_META.map(meta => meta.key);

    expect(declared).toEqual(expect.arrayContaining([...QUICK_ACTION_KEYS]));
    expect([...QUICK_ACTION_KEYS]).toEqual(expect.arrayContaining(declared));
  });

  it('gates the duty-period editor on the duty permission the page itself needs', () => {
    const action = QUICK_ACTION_META.find(meta => meta.key === 'duty_periods')!;

    expect(action.requiresPermission({ duty: true })).toBe(true);
    expect(action.requiresPermission({ duty: false })).toBe(false);
    expect(action.requiresPermission({})).toBe(false);
  });
});
