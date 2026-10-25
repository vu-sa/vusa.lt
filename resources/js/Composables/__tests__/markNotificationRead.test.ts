import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { markNotificationRead } from '../markNotificationRead';

describe('markNotificationRead', () => {
  beforeEach(() => {
    vi.stubGlobal('route', vi.fn((name: string, id: string) => `/routes/${name}/${id}`));
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(null, { status: 204 })));
  });

  afterEach(() => {
    vi.unstubAllGlobals();
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
