import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { scopedGlobals } from '@/tests/helpers/scopedGlobals';

import { markNotificationRead } from '../markNotificationRead';

const { stubGlobal, restoreGlobals } = scopedGlobals();
afterEach(restoreGlobals);

describe('markNotificationRead', () => {
  beforeEach(() => {
    stubGlobal('route', vi.fn((name: string, id: string) => `/routes/${name}/${id}`));
    stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(null, { status: 204 })));
  });

  it('posts outside the Inertia router with a request that survives the navigation it accompanies', () => {
    markNotificationRead('notif-1');

    expect(fetch).toHaveBeenCalledWith('/routes/notifications.markAsRead/notif-1', expect.objectContaining({
      method: 'POST',
      keepalive: true,
      headers: expect.objectContaining({ Accept: 'application/json' }),
    }));
  });
});
