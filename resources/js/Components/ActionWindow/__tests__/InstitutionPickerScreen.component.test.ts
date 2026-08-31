import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h, ref } from 'vue';

import InstitutionPickerScreen from '@/Components/ActionWindow/screens/InstitutionPickerScreen.vue';
import { createActionWindowProvider } from '@/Composables/useActionWindow';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const apiInstitutions: unknown[] = [];
const institutionSearch = ref({ enabled: false, tenant_ids: [] as number[] });

// Real refs, not `{ value }` literals: the template unwraps refs, and a plain
// object is truthy — `v-if="isLoading"` would pin the screen on its skeleton.
vi.mock('@/Composables/useActionWindowData', () => ({
  useActionWindowData: () => ({
    institutions: ref(apiInstitutions),
    meetings: ref([]),
    institutionSearch,
    isLoading: ref(false),
    error: ref(null),
    load: vi.fn(),
  }),
  invalidateActionWindowData: vi.fn(),
}));

const withStatus = (status: Record<string, unknown>) => {
  institutionSearch.value = { enabled: false, tenant_ids: [] };
  apiInstitutions.length = 0;
  apiInstitutions.push({
    id: '1',
    name: 'VU SA MIF',
    tenant_shortname: 'MIF',
    is_internal: true,
    meeting_pattern: null,
    activity_status: {
      status: 'healthy',
      requires_action: false,
      priority: 0,
      periodicity_days: 30,
      effective_days_since_activity: null,
      progress_percentage: null,
      last_activity_type: null,
      last_activity_at: null,
      last_meeting_at: null,
      next_meeting_at: null,
      active_check_in_until: null,
      ...status,
    },
  });

  return mount(defineComponent({
    setup() {
      const window = createActionWindowProvider();
      window.open({ flow: 'meeting.create' });
      return () => h(InstitutionPickerScreen);
    },
  }), { global: { stubs: { ...commonStubs } } });
};

describe('InstitutionPickerScreen.vue', () => {
  it('leads with the upcoming meeting when there is one', () => {
    const wrapper = withStatus({ next_meeting_at: '2026-09-15T16:00:00.000Z' });

    expect(wrapper.text()).toContain('action_window.institution.next_meeting');
    expect(wrapper.text()).not.toContain('action_window.institution.last_meeting');
  });

  /**
   * A body can hold both at once, and showing only the check-in read as wrong
   * ("no meetings until October" beside a meeting next week).
   */
  it('shows the meeting and the check-in together when both apply', () => {
    const wrapper = withStatus({
      next_meeting_at: '2026-09-15T16:00:00.000Z',
      active_check_in_until: '2026-10-01',
    });

    expect(wrapper.text()).toContain('action_window.institution.next_meeting');
    expect(wrapper.text()).toContain('action_window.institution.check_in_until_short');
  });

  it('keeps the full check-in sentence when it stands alone', () => {
    const wrapper = withStatus({ active_check_in_until: '2026-10-01' });

    expect(wrapper.text()).toContain('action_window.institution.check_in_until');
    expect(wrapper.text()).not.toContain('action_window.institution.check_in_until_short');
  });

  it('falls back to when the body last met', () => {
    const wrapper = withStatus({
      last_meeting_at: '2026-07-01T16:00:00.000Z',
      effective_days_since_activity: 21,
    });

    expect(wrapper.text()).toContain('action_window.institution.last_meeting');
  });

  it('says so when the body has never met', () => {
    const wrapper = withStatus({});

    expect(wrapper.text()).toContain('action_window.institution.no_meetings_yet');
  });

  /**
   * Only a caller who may create meetings beyond their own duties gets the wider
   * search; for everyone else the option would lead straight to a refusal.
   */
  it('hides the wider institution search without the scope for it', () => {
    const wrapper = withStatus({});

    expect(wrapper.text()).not.toContain('action_window.institution.other');
  });

  it('offers the wider institution search when the caller may create beyond their duties', async () => {
    const wrapper = withStatus({});
    institutionSearch.value = { enabled: true, tenant_ids: [16] };
    await wrapper.vm.$nextTick();

    expect(wrapper.text()).toContain('action_window.institution.other');
  });
});
