import { describe, expect, it, vi } from 'vitest';


vi.mock('@inertiajs/vue3', () => ({
  usePage: vi.fn(() => ({ props: {} })),
  router: { visit: vi.fn() },
}));

const { QUICK_ACTION_META } = await import('../useQuickActions');

describe('QUICK_ACTION_META', () => {
  it('gates the duty-period editor on the duty permission the page itself needs', () => {
    const action = QUICK_ACTION_META.find(meta => meta.key === 'duty_periods')!;

    expect(action.requiresPermission({ duty: true })).toBe(true);
    expect(action.requiresPermission({ duty: false })).toBe(false);
    expect(action.requiresPermission({})).toBe(false);
  });
});
