import { describe, test, expect, beforeEach, vi } from 'vitest';
import { nextTick, reactive } from 'vue';

const mockPageProps = reactive({
  flash: {
    access_change_warning: null as null | Record<string, unknown>,
  },
});

vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({ props: mockPageProps }),
}));

import { useAccessChangeGuard } from '../useAccessChangeGuard';

const warning = {
  lostRoles: ['Communication Coordinator'],
  severity: 'warning' as const,
};

describe('useAccessChangeGuard', () => {
  beforeEach(() => {
    mockPageProps.flash.access_change_warning = null;
  });

  test('opens the dialog with the report when a warning flashes after a guarded submit', async () => {
    const { report, open, guardedSubmit } = useAccessChangeGuard();

    const submit = vi.fn();
    guardedSubmit(submit);

    expect(submit).toHaveBeenCalledWith(false);
    expect(open.value).toBe(false);

    // Backend responds with a warning.
    mockPageProps.flash.access_change_warning = warning;
    await nextTick();

    expect(open.value).toBe(true);
    expect(report.value).toEqual(warning);
    // Flash consumed so it can't re-trigger.
    expect(mockPageProps.flash.access_change_warning).toBeNull();
  });

  test('confirm replays the submit with acknowledgement', async () => {
    const { guardedSubmit, confirm, open } = useAccessChangeGuard();

    const submit = vi.fn();
    guardedSubmit(submit);
    mockPageProps.flash.access_change_warning = warning;
    await nextTick();

    confirm();

    expect(submit).toHaveBeenLastCalledWith(true);
    expect(open.value).toBe(false);
  });

  test('ignores warnings when no guarded submit is pending', async () => {
    const { open } = useAccessChangeGuard();

    mockPageProps.flash.access_change_warning = warning;
    await nextTick();

    expect(open.value).toBe(false);
  });

  test('cancel closes the dialog without replaying', async () => {
    const { guardedSubmit, cancel, confirm, open, report } = useAccessChangeGuard();

    const submit = vi.fn();
    guardedSubmit(submit);
    mockPageProps.flash.access_change_warning = warning;
    await nextTick();

    cancel();

    expect(open.value).toBe(false);
    expect(report.value).toBeNull();
    expect(submit).toHaveBeenCalledTimes(1); // only the initial submit(false)
  });
});
