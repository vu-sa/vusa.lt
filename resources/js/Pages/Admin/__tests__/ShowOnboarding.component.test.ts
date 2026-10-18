import { ref } from 'vue';
import { mount, flushPromises, enableAutoUnmount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import ShowOnboarding from '../ShowOnboarding.vue';

import { useApiMutation } from '@/Composables/useApi';
import { checklistFixture } from '@/Components/Onboarding/__tests__/fixtures';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useApi', () => ({ useApiMutation: vi.fn() }));
enableAutoUnmount(afterEach);

const isInstalled = ref(false);
const hasPushSubscription = ref(false);
vi.mock('@/Composables/usePWA', () => ({ usePWA: () => ({ isInstalled, hasPushSubscription }) }));

const stubs = { InstallationInstructions: true, BrowserNotificationSetup: true };
const mountPage = (checklist = checklistFixture()) => mount(ShowOnboarding, {
  props: { checklist, installationTutorials: {} }, global: { stubs },
});

beforeEach(() => {
  vi.clearAllMocks();
  isInstalled.value = false;
  hasPushSubscription.value = false;
  vi.mocked(usePage).mockReturnValue(createMockPage() as ReturnType<typeof usePage>);
});

describe('ShowOnboarding', () => {
  it('does not complete steps just by opening the page', () => {
    mountPage();
    expect(useApiMutation).not.toHaveBeenCalled();
  });

  it('records a verified subscription while keeping device status separate from saved progress', async () => {
    hasPushSubscription.value = true;
    vi.mocked(useApiMutation).mockReturnValue({
      execute: vi.fn().mockResolvedValue(undefined), isSuccess: ref(true), data: ref(checklistFixture({ push: 'done' })),
    } as never);
    mountPage();
    await flushPromises();
    expect(useApiMutation).toHaveBeenCalledWith(expect.any(String), 'PATCH', { status: 'done' }, expect.any(Object));
    expect(useApiMutation).toHaveBeenCalledOnce();
  });

  it('preserves a declined notification decision on a subscribed device', async () => {
    hasPushSubscription.value = true;
    mountPage(checklistFixture({ push: 'skipped' }));
    await flushPromises();
    expect(useApiMutation).not.toHaveBeenCalled();
  });

  it('does not reopen completed setup when visiting from a different device', () => {
    const wrapper = mountPage(checklistFixture({ push: 'done', installation: 'done' }));
    expect(wrapper.find('[data-testid="reopen-push"]').exists()).toBe(true);
    expect(useApiMutation).not.toHaveBeenCalled();
  });
});
