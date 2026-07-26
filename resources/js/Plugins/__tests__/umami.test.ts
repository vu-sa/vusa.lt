import { afterEach, describe, expect, it, vi } from 'vitest';

import { trackEvent } from '@/Plugins/umami';

type WindowWithUmami = typeof globalThis & { umami?: { track: ReturnType<typeof vi.fn> } };

afterEach(() => {
  delete (window as WindowWithUmami).umami;
});

describe('trackEvent', () => {
  it('forwards the event name and payload to the tracker', () => {
    const track = vi.fn();
    (window as WindowWithUmami).umami = { track };

    trackEvent('document_click', { document_id: 42, tenant: 'MIF' });

    expect(track).toHaveBeenCalledWith('document_click', { document_id: 42, tenant: 'MIF' });
  });

  it('sends events without a payload', () => {
    const track = vi.fn();
    (window as WindowWithUmami).umami = { track };

    trackEvent('search_submitted');

    expect(track).toHaveBeenCalledWith('search_submitted', undefined);
  });

  it('is a silent no-op when the tracker is absent (admin pages, staging, blockers)', () => {
    expect((window as WindowWithUmami).umami).toBeUndefined();

    expect(() => trackEvent('document_click', { document_id: 1 })).not.toThrow();
  });
});
