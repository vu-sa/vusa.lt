import { router } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useCadenceCrud } from '../useCadenceCrud';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const draft = { start_date: '2026-07-01', end_date: '2027-06-30', start_meeting_id: null, end_meeting_id: null };

describe('useCadenceCrud', () => {
  beforeEach(() => {
    vi.mocked(router.post).mockClear();
  });

  it('keeps the page state on save, so an editor inside a deferred panel stays open', () => {
    useCadenceCrud('inst-1').create(draft);

    expect(router.post).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({ institution_id: 'inst-1' }),
      expect.objectContaining({ preserveState: true }),
    );
    expect(vi.mocked(router.post).mock.calls[0][2]).not.toHaveProperty('only');
  });

  it('reloads only the props the host page names', () => {
    useCadenceCrud('inst-1', { only: ['management'] }).create(draft);

    expect(router.post).toHaveBeenCalledWith(expect.anything(), expect.anything(), expect.objectContaining({ only: ['management'] }));
  });
});
