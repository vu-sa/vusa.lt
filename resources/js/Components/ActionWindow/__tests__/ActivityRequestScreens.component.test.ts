import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, flushPromises, enableAutoUnmount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { computed, defineComponent, h, ref, type Component } from 'vue';

import ActivityRequestCampaignScreen from '@/Components/ActionWindow/screens/ActivityRequestCampaignScreen.vue';
import { useAdminCollectionSearch } from '@/Features/Admin/AdminSearch/Composables/useAdminCollectionSearch';
import ActivityRequestInstitutionsScreen from '@/Components/ActionWindow/screens/ActivityRequestInstitutionsScreen.vue';
import ActivityRequestReviewScreen from '@/Components/ActionWindow/screens/ActivityRequestReviewScreen.vue';
import ActivityRequestModeScreen from '@/Components/ActionWindow/screens/ActivityRequestModeScreen.vue';
import ActivityRequestPeopleScreen from '@/Components/ActionWindow/screens/ActivityRequestPeopleScreen.vue';
import { createActionWindowProvider, type ActionWindowContext, type OpenOptions } from '@/Composables/useActionWindow';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const candidates = ref<unknown[]>([]);
const preview = ref<unknown[] | null>(null);
const fetching = ref(false);
const apiError = ref<string | null>(null);
const previewExecute = vi.fn(async () => {});

vi.mock('@/Composables/useApi', () => ({
  useApi: () => ({ data: candidates, error: ref(null), isFetching: ref(false), execute: vi.fn() }),
  useApiMutation: () => ({ data: preview, error: apiError, isFetching: fetching, execute: previewExecute, abort: vi.fn() }),
}));

vi.mock('@/Features/Admin/AdminSearch/Composables/useAdminCollectionSearch', () => ({
  useAdminCollectionSearch: vi.fn(() => ({ results: computed(() => candidates.value), query: ref(''), hasMoreResults: ref(false), search: vi.fn(), loadMore: vi.fn() })),
}));
vi.mock('@/Composables/useFeatureSpotlight', () => ({ useFeatureSpotlight: () => ({ isDismissed: ref(true), dismiss: vi.fn() }) }));

enableAutoUnmount(afterEach);

const status = { status: 'overdue', requires_action: true, priority: 3, periodicity_days: 30 };

function mountScreen(screen: Component, options: OpenOptions) {
  let window!: ActionWindowContext;

  const wrapper = mount(defineComponent({
    setup() {
      window = createActionWindowProvider();
      window.open(options);
      window.updateActivityRequest({ campaignType: 'activity_confirmation' });
      return () => h(screen);
    },
  }), { global: { stubs: { ...commonStubs } } });

  return { wrapper, window };
}

beforeEach(() => {
  vi.mocked(router.post).mockClear();
  candidates.value = [];
  preview.value = null;
  fetching.value = false;
  apiError.value = null;
  previewExecute.mockImplementation(async () => {});
});

describe('ActivityRequestInstitutionsScreen.vue', () => {
  it('picks several institutions and moves on to the review', async () => {
    candidates.value = [
      { id: 'a', name: 'VU SA MIF', tenant_shortname: 'MIF', activity_status: status },
      { id: 'b', name: 'VU SA FilF', tenant_shortname: 'FilF', activity_status: status },
    ];
    const { wrapper, window } = mountScreen(ActivityRequestInstitutionsScreen, { flow: 'activity.request' });

    const choices = wrapper.findAll('[data-slot="action-choice-button"]');
    await choices[0]!.trigger('click');
    await choices[1]!.trigger('click');
    await choices[0]!.trigger('click');

    expect(window.draft.activityRequest.institutions).toEqual([{ id: 'b', name: 'VU SA FilF' }]);

    await wrapper.find('[data-slot="action-window-primary"]').trigger('click');

    expect(window.current.value.id).toBe('activity.review');
  });

  it('cannot continue without an institution', () => {
    const { wrapper } = mountScreen(ActivityRequestInstitutionsScreen, { flow: 'activity.request' });

    expect(wrapper.find('[data-slot="action-window-primary"]').attributes('disabled')).toBeDefined();
  });
});

describe('ActivityRequestReviewScreen.vue', () => {
  const institutions = [{ id: 'a', name: 'VU SA MIF' }, { id: 'b', name: 'VU SA FilF' }];

  it('names who gets asked, says why an institution is left out, and sends the note', async () => {
    preview.value = [
      { institution: institutions[0], recipients: [{ id: 'u1', name: 'Ona Atstovė', period_start: '2026-09-01', period_end: '2026-10-03', delivery_mode: 'immediate' }], period_start: '2026-09-01', skip_reason: null },
      { institution: institutions[1], recipients: [], period_start: '2026-09-01', skip_reason: 'no_recipients' },
    ];
    const { wrapper } = mountScreen(ActivityRequestReviewScreen, { flow: 'activity.request', institutions });

    await flushPromises();
    expect(wrapper.text()).toContain('activity_requests.skip.no_recipients');

    await wrapper.find('textarea').setValue('Iki penktadienio');
    await wrapper.find('[data-slot="action-window-primary"]').trigger('click');

    expect(router.post).toHaveBeenCalledWith(
      expect.stringContaining('institutions.activity-requests.store'),
      { institution_ids: ['a', 'b'], campaign_type: 'activity_confirmation', note: 'Iki penktadienio' },
      expect.any(Object),
    );
  });

  it('does not send when every institution is left out', async () => {
    preview.value = [
      { institution: institutions[0], recipients: [], period_start: '2026-09-01', skip_reason: 'already_asked' },
    ];
    const { wrapper } = mountScreen(ActivityRequestReviewScreen, { flow: 'activity.request', institutions: [institutions[0]!] });

    await wrapper.find('[data-slot="action-window-primary"]').trigger('click');

    expect(router.post).not.toHaveBeenCalled();
  });

  it('hides empty exclusions and uses bordered editing controls and branded section headings', async () => {
    preview.value = [{ institution: institutions[0], recipients: [{ id: 'u', name: 'Rep', period_start: '2026-09-01', period_end: '2026-10-03', delivery_mode: 'immediate' }], excluded_recipients: [], skip_reason: null }];
    const { wrapper, window } = mountScreen(ActivityRequestReviewScreen, { flow: 'activity.request', institutions: [institutions[0]!] });
    await flushPromises();
    expect(wrapper.find('[data-slot="activity-request-exclusions"]').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('activity_requests.skip_section');
    const editButtons = wrapper.findAll('button').filter(button => /change_campaign|change_institutions/.test(button.text()));
    expect(editButtons).toHaveLength(2);
    editButtons.forEach(button => expect(button.classes()).toContain('border-border'));
    expect(wrapper.find('h2 svg').classes()).toContain('text-brand');
    window.updateActivityRequest({ campaignType: 'missing_meetings' });
    await flushPromises();
    expect(wrapper.text()).toContain('activity_requests.review_missing_hint');
    expect(wrapper.text()).not.toContain('action_window.activity_request.review.no_sign_in');
  });
});

describe('campaign choice and selection stability', () => {
  it('asks how to pick even when institutions are preselected', async () => {
    const { wrapper, window } = mountScreen(ActivityRequestCampaignScreen, { flow: 'activity.request', institutions: [{ id: 'a', name: 'A' }] });
    expect(window.current.value.id).toBe('activity.campaign');
    await wrapper.findAll('[data-slot="action-choice-button"]')[1]!.trigger('click');
    expect(window.draft.activityRequest.campaignType).toBe('missing_meetings');
    expect(window.current.value.id).toBe('activity.mode');
  });

  it('unpicks a preselected institution nobody can be asked about, so it is not dropped at the review', async () => {
    candidates.value = ['a', 'b'].map(id => ({ id, name: id.toUpperCase(), tenant_id: 1, tenant_shortname: 'MIF', activity_status: status }));
    preview.value = [
      { institution: { id: 'a', name: 'A' }, recipients: [], excluded_recipients: [], skip_reason: 'already_asked' },
      { institution: { id: 'b', name: 'B' }, recipients: [{ id: 'u', name: 'Rep', period_start: '2026-09-01', period_end: '2026-10-03', delivery_mode: 'immediate', skip_reason: null }], excluded_recipients: [], skip_reason: null },
    ];
    const { wrapper, window } = mountScreen(ActivityRequestInstitutionsScreen, { flow: 'activity.request', institutions: [{ id: 'a', name: 'A' }, { id: 'b', name: 'B' }] });
    await flushPromises();

    expect(window.draft.activityRequest.institutions.map(item => item.id)).toEqual(['b']);
    const blocked = wrapper.findAll('[data-slot="action-choice-button"]').find(row => row.text().includes('activity_requests.skip.already_asked'));
    expect(blocked!.attributes('disabled')).toBeDefined();
  });

  it('pins the initial group and toggling never reorders rows', async () => {
    candidates.value = ['a', 'b', 'c'].map(id => ({ id, name: id.toUpperCase(), tenant_id: 1, tenant_shortname: 'MIF', activity_status: status }));
    const { wrapper, window } = mountScreen(ActivityRequestInstitutionsScreen, { flow: 'activity.request', institutions: [{ id: 'c', name: 'C' }] });
    const rowTitles = () => wrapper.findAll('[data-slot="action-choice-button"]').map(row => row.text());
    const before = rowTitles();
    expect(before[0]).toContain('C');
    await wrapper.findAll('[data-slot="action-choice-button"]')[1]!.trigger('click');
    await wrapper.findAll('[data-slot="action-choice-button"]')[0]!.trigger('click');
    expect(rowTitles()).toEqual(before);
    expect(window.draft.activityRequest.pinned.map(item => item.id)).toEqual(['c']);
  });

  it('includes tenant filtering in the scoped Typesense query and retains selections', async () => {
    candidates.value = [{ id: 'a', name: 'A', tenant_id: 1, tenant_shortname: 'MIF', activity_status: status }];
    const { wrapper, window } = mountScreen(ActivityRequestInstitutionsScreen, { flow: 'activity.request', institutions: [{ id: 'a', name: 'A' }] });
    await wrapper.find('select').setValue('1');
    const options = vi.mocked(useAdminCollectionSearch).mock.calls.at(-1)![0];
    expect((options.baseFilterBy as { value: string }).value).toBe('id:[a] && tenant_ids:[1]');
    expect(window.draft.activityRequest.institutions.map(item => item.id)).toEqual(['a']);
  });

  it('shows the selection limit and prevents adding a 101st institution', async () => {
    candidates.value = [{ id: 'extra', name: 'Extra', tenant_id: 1, tenant_shortname: 'MIF', activity_status: status }];
    const institutions = Array.from({ length: 100 }, (_, index) => ({ id: String(index), name: String(index) }));
    const { wrapper, window } = mountScreen(ActivityRequestInstitutionsScreen, { flow: 'activity.request', institutions });
    expect(wrapper.text()).toContain('100/100');
    expect(wrapper.find('[data-slot="action-choice-button"]').attributes('disabled')).toBeDefined();
    await wrapper.find('[data-slot="action-choice-button"]').trigger('click');
    expect(window.draft.activityRequest.institutions).toHaveLength(100);
  });
});

describe('preview validity', () => {
  const institutions = [{ id: 'a', name: 'A' }];
  const entry = { institution: institutions[0], recipients: [{ id: 'u', name: 'Rep', period_start: '2026-09-01', period_end: '2026-10-03', delivery_mode: 'digest' }], excluded_recipients: [], skip_reason: null };

  it('separates recipients and people whose email preferences suppress immediate mail', async () => {
    preview.value = [entry];
    const { wrapper } = mountScreen(ActivityRequestReviewScreen, { flow: 'activity.request', institutions });
    await flushPromises();
    expect(wrapper.text()).toContain('activity_requests.send_section', 'activity_requests.skip_section');
    expect(wrapper.find('[data-slot="activity-request-exclusions"]').text()).toContain('activity_requests.delivery.digest');
  });

  it('cannot submit a failed or loading preview', async () => {
    preview.value = [entry];
    apiError.value = 'failed';
    const { wrapper } = mountScreen(ActivityRequestReviewScreen, { flow: 'activity.request', institutions });
    await flushPromises();
    await wrapper.find('[data-slot="action-window-primary"]').trigger('click');
    expect(router.post).not.toHaveBeenCalled();
    apiError.value = null;
    fetching.value = true;
    await wrapper.find('[data-slot="action-window-primary"]').trigger('click');
    expect(router.post).not.toHaveBeenCalled();
  });

  it('cannot submit the previous preview while changed targeting is being refreshed', async () => {
    preview.value = [entry];
    const { wrapper, window } = mountScreen(ActivityRequestReviewScreen, { flow: 'activity.request', institutions });
    await flushPromises();
    let finish!: () => void;
    previewExecute.mockImplementation(() => new Promise<void>((resolve) => {
      finish = resolve;
    }));
    window.updateActivityRequest({ campaignType: 'missing_meetings' });
    await wrapper.find('[data-slot="action-window-primary"]').trigger('click');
    expect(router.post).not.toHaveBeenCalled();
    finish();
    await flushPromises();
  });
});

describe('left-out rows are disabled before they are picked', () => {
  const rep = { id: 'u', name: 'Ona Atstovė', period_start: '2026-09-01', period_end: '2026-10-03', delivery_mode: 'immediate', skip_reason: null };

  it('disables an institution with its reason in place, and notes a partly asked one', async () => {
    candidates.value = [
      { id: 'a', name: 'Asked', tenant_id: 1, tenant_shortname: 'MIF', activity_status: status },
      { id: 'b', name: 'Partly', tenant_id: 1, tenant_shortname: 'MIF', activity_status: status },
    ];
    preview.value = [
      { institution: { id: 'a', name: 'Asked' }, recipients: [], excluded_recipients: [{ ...rep, skip_reason: 'already_asked' }], skip_reason: 'already_asked' },
      { institution: { id: 'b', name: 'Partly' }, recipients: [rep], excluded_recipients: [{ ...rep, id: 'v', skip_reason: 'meeting_recorded' }], skip_reason: null },
    ];
    const { wrapper, window } = mountScreen(ActivityRequestInstitutionsScreen, { flow: 'activity.request' });
    await flushPromises();

    const rows = wrapper.findAll('[data-slot="action-choice-button"]');
    expect(rows[0]!.text()).toContain('Asked');
    expect(rows[0]!.text()).toContain('activity_requests.skip.already_asked');
    expect(rows[0]!.attributes('disabled')).toBeDefined();
    expect(rows[1]!.text()).toContain('activity_requests.partial_recipients');
    await rows[0]!.trigger('click');
    expect(window.draft.activityRequest.institutions).toEqual([]);
  });

  it('disables a person whose every institution is left out', async () => {
    candidates.value = [
      { id: 'u', name: 'Ona Atstovė', institutions: [{ id: 'a', name: 'Senatas' }] },
      { id: 'w', name: 'Jonas Jonaitis', institutions: [{ id: 'b', name: 'SPK' }] },
    ];
    preview.value = [
      { institution: { id: 'a', name: 'Senatas' }, recipients: [rep], excluded_recipients: [], skip_reason: null },
      { institution: { id: 'b', name: 'SPK' }, recipients: [], excluded_recipients: [{ ...rep, id: 'w', skip_reason: 'already_asked' }], skip_reason: 'already_asked' },
    ];
    const { wrapper, window } = mountScreen(ActivityRequestPeopleScreen, { flow: 'activity.request' });
    await flushPromises();

    const [ona, jonas] = wrapper.findAll('[data-slot="action-choice-button"]');
    expect(jonas!.attributes('disabled')).toBeDefined();
    expect(jonas!.text()).toContain('activity_requests.skip.already_asked');
    await ona!.trigger('click');
    expect(window.draft.activityRequest.people.map(person => person.id)).toEqual(['u']);
    await wrapper.find('[data-slot="action-window-primary"]').trigger('click');
    expect(window.current.value.id).toBe('activity.review');
  });
});

describe('picking by representative', () => {
  it('goes from the question to the choice of how to pick, and on to people', async () => {
    const { wrapper, window } = mountScreen(ActivityRequestCampaignScreen, { flow: 'activity.request' });
    await wrapper.findAll('[data-slot="action-choice-button"]')[0]!.trigger('click');
    expect(window.current.value.id).toBe('activity.mode');
  });

  it('chooses the people picker from the mode screen', async () => {
    const { wrapper, window } = mountScreen(ActivityRequestModeScreen, { flow: 'activity.request' });
    await wrapper.findAll('[data-slot="action-choice-button"]')[1]!.trigger('click');
    expect(window.draft.activityRequest.mode).toBe('people');
    expect(window.current.value.id).toBe('activity.people');
  });

  it('groups the review by person and sends exactly the ticked pairs', async () => {
    preview.value = [
      { institution: { id: 'a', name: 'Senatas' }, recipients: [{ id: 'u', name: 'Ona Atstovė', period_start: '2026-09-01', period_end: '2026-10-03', delivery_mode: 'immediate', skip_reason: null }], excluded_recipients: [], skip_reason: null },
      { institution: { id: 'b', name: 'SPK' }, recipients: [{ id: 'u', name: 'Ona Atstovė', period_start: '2026-09-01', period_end: '2026-10-03', delivery_mode: 'immediate', skip_reason: null }], excluded_recipients: [], skip_reason: null },
    ];
    const { wrapper, window } = mountScreen(ActivityRequestReviewScreen, { flow: 'activity.request' });
    window.updateActivityRequest({ mode: 'people', people: [{ id: 'u', name: 'Ona Atstovė', institutions: [{ id: 'a', name: 'Senatas' }, { id: 'b', name: 'SPK' }] }] });
    await flushPromises();

    const groups = wrapper.findAll('[data-slot="activity-request-preview"] > div');
    expect(groups).toHaveLength(1);
    expect(groups[0]!.text()).toContain('Ona Atstovė');
    await wrapper.findAll('[data-slot="activity-request-row"] button')[1]!.trigger('click');
    await wrapper.find('[data-slot="action-window-primary"]').trigger('click');

    expect(router.post).toHaveBeenCalledWith(
      expect.stringContaining('institutions.activity-requests.store'),
      expect.objectContaining({ institution_ids: ['a', 'b'], recipients: [{ institution_id: 'a', user_id: 'u' }] }),
      expect.any(Object),
    );
  });

  it('unticking someone in the institution review narrows the send to the ticked people', async () => {
    const recipient = (id: string) => ({ id, name: id, period_start: '2026-09-01', period_end: '2026-10-03', delivery_mode: 'immediate', skip_reason: null });
    preview.value = [{ institution: { id: 'a', name: 'Senatas' }, recipients: [recipient('u'), recipient('w')], excluded_recipients: [], skip_reason: null }];
    const { wrapper } = mountScreen(ActivityRequestReviewScreen, { flow: 'activity.request', institutions: [{ id: 'a', name: 'Senatas' }] });
    await flushPromises();

    await wrapper.findAll('[data-slot="activity-request-row"] button')[0]!.trigger('click');
    await wrapper.find('[data-slot="action-window-primary"]').trigger('click');

    expect(router.post).toHaveBeenCalledWith(
      expect.any(String),
      expect.objectContaining({ recipients: [{ institution_id: 'a', user_id: 'w' }] }),
      expect.any(Object),
    );
  });
});
