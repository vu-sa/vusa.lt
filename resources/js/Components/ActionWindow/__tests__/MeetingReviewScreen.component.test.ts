import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import { usePage } from '@inertiajs/vue3';

import MeetingReviewScreen from '@/Components/ActionWindow/screens/MeetingReviewScreen.vue';
import { createActionWindowProvider, type ActionWindowContext, type ActionWindowInstitutionRef } from '@/Composables/useActionWindow';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const apiInstitutions: unknown[] = [];

vi.mock('@/Composables/useActionWindowData', () => ({
  useActionWindowData: () => ({
    institutions: { value: apiInstitutions },
    meetings: { value: [] },
    isLoading: { value: false },
    error: { value: null },
    load: vi.fn(),
  }),
  invalidateActionWindowData: vi.fn(),
}));

/**
 * Only VU SA's own bodies are announced in the public calendar. The server refuses the
 * rest, but a checkbox that always fails is worse than no checkbox — so the review must
 * not offer it, whether the scope came from the caller or from the window's own fetch.
 */
const mountReview = (institution: ActionWindowInstitutionRef) => {
  vi.mocked(usePage).mockReturnValue(createMockPage());

  let window!: ActionWindowContext;

  const wrapper = mount(defineComponent({
    setup() {
      window = createActionWindowProvider();
      window.open({ flow: 'meeting.create', institution });
      return () => h(MeetingReviewScreen);
    },
  }), { global: { stubs: { ...commonStubs } } });

  return { wrapper, window };
};

const announceCheckbox = (wrapper: ReturnType<typeof mount>) =>
  wrapper.find('[data-slot="checkbox"], button[role="checkbox"]');

describe('MeetingReviewScreen.vue', () => {
  beforeEach(() => {
    apiInstitutions.length = 0;
    vi.mocked(usePage).mockReset();
  });

  it('offers the calendar announcement for a VU SA body', async () => {
    const { wrapper } = mountReview({ id: '1', name: 'VU SA Parlamentas', isInternal: true });
    await flushPromises();

    expect(announceCheckbox(wrapper).exists()).toBe(true);
    expect(wrapper.text()).toContain('meetings.announce.review_checkbox_label');
  });

  it('hides it for a body VU SA only delegates into', async () => {
    const { wrapper } = mountReview({ id: '2', name: 'VU Senatas', isInternal: false });
    await flushPromises();

    expect(announceCheckbox(wrapper).exists()).toBe(false);
  });

  it('falls back to the fetched institution when the caller did not say', async () => {
    apiInstitutions.push({ id: '3', name: 'VU SA MIF', is_internal: true, activity_status: {} });

    const { wrapper } = mountReview({ id: '3', name: 'VU SA MIF' });
    await flushPromises();

    expect(announceCheckbox(wrapper).exists()).toBe(true);
  });

  it('stays hidden when nothing knows the scope', async () => {
    const { wrapper } = mountReview({ id: '4', name: 'Nežinoma' });
    await flushPromises();

    expect(announceCheckbox(wrapper).exists()).toBe(false);
  });
});
