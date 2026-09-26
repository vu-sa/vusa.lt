import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
  usePage: vi.fn(() => ({ props: {} })),
  router: { visit: vi.fn() },
}));

const { quickActionGradient } = await import('../useQuickActions');

describe('quick action presentation', () => {
  it('keeps a presentation tint for the server-provided duty-period action', () => {
    expect(quickActionGradient('duty_periods')).toContain('sky');
  });
});
