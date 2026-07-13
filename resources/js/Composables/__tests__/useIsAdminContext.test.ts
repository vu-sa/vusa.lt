import { describe, it, expect, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { useIsAdminContext } from '@/Composables/useIsAdminContext';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('useIsAdminContext', () => {
  it('returns true for /mano paths', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ app: { path: '/mano/users' } }));

    const result = useIsAdminContext();
    expect(result.value).toBe(true);
  });

  it('returns false for public paths', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ app: { path: '/lt/naujienos' } }));

    const result = useIsAdminContext();
    expect(result.value).toBe(false);
  });
});
