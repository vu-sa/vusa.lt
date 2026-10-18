import { defineComponent, ref } from 'vue';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useOnboarding } from '../useOnboarding';

import { useApiMutation } from '@/Composables/useApi';
import { checklistFixture } from '@/Components/Onboarding/__tests__/fixtures';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useApi', () => ({ useApiMutation: vi.fn() }));

const execute = vi.fn();
let result: ReturnType<typeof useOnboarding>;

beforeEach(() => {
  vi.clearAllMocks();
  vi.mocked(usePage).mockReturnValue(createMockPage() as ReturnType<typeof usePage>);
  execute.mockResolvedValue(undefined);
});

function setup(success: boolean, response = checklistFixture({ push: 'skipped' })) {
  vi.mocked(useApiMutation).mockReturnValue({ execute, isSuccess: ref(success), data: ref(response) } as never);
  mount(defineComponent({
    setup() {
      result = useOnboarding(checklistFixture());
      return () => null;
    },
  }));
}

describe('useOnboarding', () => {
  it('updates progress and the shared install preference only after a successful save', async () => {
    setup(true);
    expect(await result.updateItem('push', 'skipped')).toBe(true);
    expect(result.checklist.value.resolved).toBe(1);
    expect(usePage().props.auth?.user.onboarding_progress['getting-started-v1'].items.push.status).toBe('skipped');
  });

  it('keeps prior progress on a failed save and allows retry', async () => {
    setup(false);
    expect(await result.updateItem('push', 'skipped')).toBe(false);
    expect(result.failed.value).toBe(true);
    expect(result.checklist.value.resolved).toBe(0);
    expect(result.saving.value).toBe(false);
    vi.mocked(useApiMutation).mockReturnValue({ execute, isSuccess: ref(true), data: ref(checklistFixture({ push: 'skipped' })) } as never);
    expect(await result.updateItem('push', 'skipped')).toBe(true);
    expect(result.failed.value).toBe(false);
  });

  it('hides the summary without marking any items done', async () => {
    setup(true, { ...checklistFixture(), hidden: true });
    await result.setHidden(true);
    expect(result.checklist.value.hidden).toBe(true);
    expect(result.checklist.value.resolved).toBe(0);
  });

  it('does not apply a response after the signed in account has changed', async () => {
    setup(true);
    execute.mockImplementationOnce(async () => {
      usePage().props.auth!.user.id = 'another-account';
    });
    expect(await result.updateItem('push', 'skipped')).toBe(false);
    expect(result.checklist.value.resolved).toBe(0);
  });
});
