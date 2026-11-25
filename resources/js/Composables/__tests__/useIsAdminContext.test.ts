import { describe, it, expect, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { useIsAdminContext } from '@/Composables/useIsAdminContext';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('useIsAdminContext', () => {
  it.each(['mano', '/mano', 'mano/users', '/mano/users'])('returns true for %s', (path) => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ app: { path } }));

    const result = useIsAdminContext();
    expect(result.value).toBe(true);
  });

  it.each(['lt/naujienos', 'manoverse/users'])('returns false for %s', (path) => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ app: { path } }));

    const result = useIsAdminContext();
    expect(result.value).toBe(false);
  });
});
