import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, flushPromises, enableAutoUnmount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';
import { computed, defineComponent, h, ref, type Component } from 'vue';

import ActivityRequestCampaignScreen from '@/Components/ActionWindow/screens/ActivityRequestCampaignScreen.vue';
import { useAdminCollectionSearch } from '@/Features/Admin/AdminSearch/Composables/useAdminCollectionSearch';
import ActivityRequestInstitutionsScreen from '@/Components/ActionWindow/screens/ActivityRequestInstitutionsScreen.vue';
import ActivityRequestReviewScreen from '@/Components/ActionWindow/screens/ActivityRequestReviewScreen.vue';
import ActivityRequestModeScreen from '@/Components/ActionWindow/screens/ActivityRequestModeScreen.vue';
import ActivityRequestPeopleScreen from '@/Components/ActionWindow/screens/ActivityRequestPeopleScreen.vue';
import { createActionWindowProvider, type ActionWindowContext, type OpenOptions } from '@/Composables/useActionWindow';
import { createMockPage } from '@/tests/helpers/createMockPage';
import ActivityRequestFilters from '@/Components/ActionWindow/ActivityRequestFilters.vue';
import { MultiSelect } from '@/Components/ui/multi-select';
import { InstitutionScope } from '@/Types/enums';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const candidates = ref<unknown[]>([]);
const preview = ref<unknown[] | null>(null);
const fetching = ref(false);
const apiError = ref<string | null>(null);
const previewExecute = vi.fn(async (_payload?: { institution_ids: string[] }) => {});

vi.mock('@/Composables/useApi', () => ({
  useApi: () => ({ data: candidates, error: ref(null), isFetching: ref(false), execute: vi.fn() }),
  useApiMutation: (_url: string, _method: string, payload: { value: { institution_ids: string[] } }) => ({
    data: preview, error: apiError, isFetching: fetching, execute: () => previewExecute(payload.value), abort: vi.fn(),
  }),
}));

vi.mock('@/Features/Admin/AdminSearch/Composables/useAdminCollectionSearch', () => ({
  useAdminCollectionSearch: vi.fn(() => ({ results: computed(() => candidates.value), status: ref('idle'), error: ref(null), query: ref(''), hasMoreResults: ref(false), search: vi.fn(), loadMore: vi.fn() })),
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
  const candidate = (id: string, tenant = 1, scope = InstitutionScope.University) => ({
    id, name: `Institution ${id}`, tenant_id: tenant, tenant_shortname: `Tenant ${tenant}`, governance_scope: scope, activity_status: status,
  });

  it('inherits several tenants and VU, filters pinned institutions, and prunes on filter changes', async () => {
    const items = [candidate('a'), candidate('b', 2), candidate('c', 3), candidate('d', 1, InstitutionScope.Vusa)];
    candidates.value = items;
    const { wrapper, window } = mountScreen(ActivityRequestInstitutionsScreen, {
      flow: 'activity.request', institutions: items,
      activityRequestFilters: { tenantIds: ['1', '2'], governanceScope: InstitutionScope.University },
    });
    expect(window.draft.activityRequest.institutions.map(item => item.id)).toEqual(['a', 'b']);
    expect(wrapper.findAll('[data-slot="action-choice-button"]')).toHaveLength(2);
    expect(wrapper.get('select').element.value).toBe('vu');
    wrapper.getComponent(MultiSelect).vm.$emit('update:modelValue', [{ id: '2', label: 'Tenant 2' }]);
    await flushPromises();
    expect(window.draft.activityRequest.institutions.map(item => item.id)).toEqual(['b']);
    expect(wrapper.findAll('[data-slot="action-choice-button"]')).toHaveLength(1);
    await wrapper.get('select').setValue('vusa');
    expect(window.draft.activityRequest.institutions).toEqual([]);
    expect(wrapper.findAll('[data-slot="action-choice-button"]')).toHaveLength(0);
  });

  it('lists the central office first, then units by name, then student initiatives', () => {
    const defaultPage = vi.mocked(usePage).getMockImplementation()!;
    const page = createMockPage();
    page.props.tenants = [
      { id: 1, shortname: 'VU SA MIF', type: 'padalinys' },
      { id: 2, shortname: 'VU SA', type: 'pagrindinis' },
      { id: 3, shortname: 'VU SA CHGF', type: 'padalinys' },
      { id: 4, shortname: 'Klubas', type: 'pkp' },
    ] as typeof page.props.tenants;
    vi.mocked(usePage).mockReturnValue(page);
    const items = [candidate('a', 4), candidate('b', 1), candidate('c', 2), candidate('d', 3)]
      .map(item => ({ ...item, tenant_shortname: page.props.tenants!.find(tenant => tenant.id === item.tenant_id)!.shortname }));
    candidates.value = items;

    const { wrapper } = mountScreen(ActivityRequestInstitutionsScreen, { flow: 'activity.request' });

    expect(wrapper.getComponent(MultiSelect).props('options').map((option: { label: string }) => option.label))
      .toEqual(['VU SA', 'VU SA CHGF', 'VU SA MIF', 'Klubas']);
    vi.mocked(usePage).mockImplementation(defaultPage);
  });

  it('caps an oversized initial selection after filtering and explains the limit', () => {
    const items = Array.from({ length: 101 }, (_, index) => candidate(String(index)));
    candidates.value = [candidate('outside', 2), ...items];
    const { wrapper, window } = mountScreen(ActivityRequestInstitutionsScreen, {
      flow: 'activity.request', institutions: [candidate('outside', 2), ...items],
      activityRequestFilters: { tenantIds: ['1'], governanceScope: InstitutionScope.University },
    });
    expect(window.draft.activityRequest.institutions.map(item => item.id)).toEqual(items.slice(0, 100).map(item => item.id));
    expect(wrapper.text()).toContain('activity_requests.limit_reached');
  });

  const paginatedSearch = (items: ReturnType<typeof candidate>[]) => {
    const results = ref(items.slice(0, 20));
    const hasMoreResults = computed(() => results.value.length < items.length);
    const loadMore = vi.fn(async () => {
      results.value = items.slice(0, results.value.length + 20);
    });
    vi.mocked(useAdminCollectionSearch).mockReturnValueOnce({
      results, hasMoreResults, loadMore, status: ref('idle'), error: ref(null), query: ref(''), search: vi.fn(),
    } as unknown as ReturnType<typeof useAdminCollectionSearch>);
    return { results, loadMore };
  };

  const eligiblePreview = (ids: string[], blocked: string[] = []) => ids.map(id => ({
    institution: { id, name: id }, skip_reason: blocked.includes(id) ? 'already_asked' : null,
    recipients: blocked.includes(id) ? [] : [{ id: `rep-${id}`, name: id, period_start: '2026-01-01', period_end: '2026-10-27', delivery_mode: 'immediate', skip_reason: null }],
  }));

  it('loads matching pages, skips blocked institutions and stops at 100 distinct selections', async () => {
    const items = Array.from({ length: 102 }, (_, index) => candidate(String(index)));
    candidates.value = items;
    const { loadMore } = paginatedSearch(items);
    previewExecute.mockImplementation(async (payload) => {
      preview.value = eligiblePreview(payload!.institution_ids, ['0']);
    });
    const { wrapper, window } = mountScreen(ActivityRequestInstitutionsScreen, { flow: 'activity.request', institutions: [items[1]!] });
    await wrapper.get('[data-slot="activity-select-all"]').trigger('click');
    await flushPromises();
    expect(loadMore).toHaveBeenCalledTimes(5);
    expect(window.draft.activityRequest.institutions).toHaveLength(100);
    expect(new Set(window.draft.activityRequest.institutions.map(item => item.id)).size).toBe(100);
    expect(window.draft.activityRequest.institutions.some(item => item.id === '0')).toBe(false);
    expect(wrapper.text()).toContain('activity_requests.limit_reached');
    await wrapper.get('[data-slot="activity-deselect-all"]').trigger('click');
    expect(window.draft.activityRequest.institutions).toEqual([]);
    expect(wrapper.get('[data-slot="action-window-primary"]').attributes('disabled')).toBeDefined();
  });

  it('clears selections hidden by text search and never bulk selects pinned nonmatches', async () => {
    const items = [candidate('a'), candidate('b')];
    candidates.value = items;
    const search = paginatedSearch(items);
    previewExecute.mockImplementation(async (payload) => {
      preview.value = eligiblePreview(payload!.institution_ids);
    });
    const { wrapper, window } = mountScreen(ActivityRequestInstitutionsScreen, { flow: 'activity.request', institutions: items });
    const controller = vi.mocked(useAdminCollectionSearch).mock.results.at(-1)!.value;
    controller.query.value = 'Institution b';
    search.results.value = [items[1]!];
    await flushPromises();
    expect(wrapper.findAll('[data-slot="action-choice-button"]')).toHaveLength(1);
    await wrapper.get('[data-slot="activity-deselect-all"]').trigger('click');
    expect(window.draft.activityRequest.institutions).toEqual([]);
    // The search has no additional matches.
    controller.hasMoreResults = ref(false);
    await wrapper.get('[data-slot="activity-select-all"]').trigger('click');
    await flushPromises();
    expect(window.draft.activityRequest.institutions.map(item => item.id)).toEqual(['b']);
  });

  it('does not select unchecked candidates when eligibility fails', async () => {
    candidates.value = [candidate('a')];
    apiError.value = 'Failed';
    preview.value = eligiblePreview(['a']);
    const { wrapper, window } = mountScreen(ActivityRequestInstitutionsScreen, { flow: 'activity.request' });
    await wrapper.get('[data-slot="activity-select-all"]').trigger('click');
    await flushPromises();
    expect(window.draft.activityRequest.institutions).toEqual([]);
    expect(wrapper.get('[role="alert"]').text()).toContain('action_window.common.error');
  });

  it.each(['filters', 'campaign', 'navigation'] as const)('cancels bulk selection when %s changes during eligibility loading', async (change) => {
    candidates.value = [candidate('a')];
    let finish!: () => void;
    previewExecute.mockImplementationOnce(() => new Promise<void>((resolve) => {
      finish = resolve;
    }));
    const { wrapper, window } = mountScreen(ActivityRequestInstitutionsScreen, { flow: 'activity.request' });
    await wrapper.get('[data-slot="activity-select-all"]').trigger('click');
    if (change === 'filters') await wrapper.get('select').setValue('vusa');
    if (change === 'campaign') window.updateActivityRequest({ campaignType: 'missing_meetings' });
    if (change === 'navigation') window.goTo('activity.mode');
    preview.value = eligiblePreview(['a']);
    finish();
    await flushPromises();
    expect(window.draft.activityRequest.institutions).toEqual([]);
  });

  it('stops loading more matches after a search failure', async () => {
    const items = Array.from({ length: 21 }, (_, index) => candidate(String(index)));
    candidates.value = items;
    const { loadMore } = paginatedSearch(items);
    loadMore.mockImplementation(async () => {});
    previewExecute.mockImplementation(async (payload) => {
      preview.value = eligiblePreview(payload!.institution_ids);
    });
    const { wrapper, window } = mountScreen(ActivityRequestInstitutionsScreen, { flow: 'activity.request' });
    await wrapper.get('[data-slot="activity-select-all"]').trigger('click');
    await flushPromises();
    expect(loadMore).toHaveBeenCalledTimes(1);
    expect(window.draft.activityRequest.institutions).toHaveLength(20);
    expect(wrapper.find('[role="alert"]').exists()).toBe(true);
  });

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

  it.each(['institutions', 'people'] as const)('bulk toggles sendable recipients in %s mode and submits the remaining pair', async (mode) => {
    const recipient = (id: string) => ({ id, name: id, period_start: '2026-01-01', period_end: '2026-10-27', delivery_mode: 'immediate', skip_reason: null });
    preview.value = [{ institution: institutions[0], recipients: [recipient('u'), recipient('w')], excluded_recipients: [{ ...recipient('excluded'), skip_reason: 'already_asked' }], skip_reason: null }];
    const { wrapper, window } = mountScreen(ActivityRequestReviewScreen, { flow: 'activity.request', institutions: [institutions[0]!] });
    window.updateActivityRequest({ mode, people: [{ id: 'u', name: 'u', institutions: [institutions[0]!] }, { id: 'w', name: 'w', institutions: [institutions[0]!] }] });
    await flushPromises();
    await wrapper.get('[data-slot="review-deselect-all"]').trigger('click');
    expect(wrapper.get('[data-slot="action-window-primary"]').attributes('disabled')).toBeDefined();
    expect(wrapper.findAll('[data-slot="activity-request-row"] button[aria-checked="true"]')).toHaveLength(0);
    await wrapper.get('[data-slot="action-window-primary"]').trigger('click');
    expect(router.post).not.toHaveBeenCalled();
    await wrapper.get('[data-slot="review-select-all"]').trigger('click');
    expect(wrapper.findAll('[data-slot="activity-request-row"] button[aria-checked="true"]')).toHaveLength(2);
    await wrapper.findAll('[data-slot="activity-request-row"] button')[1]!.trigger('click');
    await wrapper.get('[data-slot="action-window-primary"]').trigger('click');
    expect(router.post).toHaveBeenCalledWith(expect.any(String), expect.objectContaining({
      recipients: [{ institution_id: 'a', user_id: 'u' }],
    }), expect.any(Object));
  });

  it.each(['institutions', 'people'] as const)('shows ended check-in context when selecting by %s', async (mode) => {
    preview.value = [{
      institution: institutions[0],
      activity_status: { ...status, status: 'recent_check_in', last_activity_type: 'check_in', last_completed_check_in_at: '2026-10-16' },
      recipients: [{ id: 'u1', name: 'Ona', period_start: '2026-10-17', period_end: '2026-10-19', delivery_mode: 'immediate' }],
      skip_reason: null,
    }];
    const { wrapper, window } = mountScreen(ActivityRequestReviewScreen, { flow: 'activity.request', institutions: [institutions[0]!] });
    window.updateActivityRequest({ mode, people: mode === 'people' ? [{ id: 'u1', name: 'Ona', institutions: [institutions[0]!] }] : [] });
    await flushPromises();

    expect(wrapper.find('[data-slot="recent-check-in-context"]').text()).toContain('activity_requests.recent_check_in');
    expect(wrapper.text()).toContain('activity_requests.check_in_ended');
    expect(wrapper.find('[data-slot="action-window-primary"]').attributes('disabled')).toBeUndefined();
  });

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

  it('tells a staging tester the email comes only to them', () => {
    const defaultPage = vi.mocked(usePage).getMockImplementation()!;
    vi.mocked(usePage).mockReturnValue(createMockPage({ staging: { isStaging: true, filesReadOnly: true, sharepointReadOnly: true, mailToRequester: true } }));

    const { wrapper } = mountScreen(ActivityRequestReviewScreen, { flow: 'activity.request', institutions: [institutions[0]!] });

    expect(wrapper.get('[data-slot="staging-note"]').text()).toContain('staging.topics.mail');
    vi.mocked(usePage).mockImplementation(defaultPage);
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
    wrapper.getComponent(ActivityRequestFilters).getComponent(MultiSelect).vm.$emit('update:modelValue', [{ id: '1', label: 'MIF' }]);
    await flushPromises();
    const options = vi.mocked(useAdminCollectionSearch).mock.calls.at(-1)![0];
    expect((options.baseFilterBy as { value: string }).value).toBe('id:[a] && tenant_ids:[1]');
    expect(window.draft.activityRequest.institutions.map(item => item.id)).toEqual(['a']);
  });

  it('shows the selection limit and prevents adding a 101st institution', async () => {
    const institutions = Array.from({ length: 100 }, (_, index) => ({ id: String(index), name: String(index) }));
    candidates.value = [...institutions, { id: 'extra', name: 'Extra' }].map(item => ({ ...item, tenant_id: 1, tenant_shortname: 'MIF', activity_status: status }));
    const { wrapper, window } = mountScreen(ActivityRequestInstitutionsScreen, { flow: 'activity.request', institutions });
    expect(wrapper.text()).toContain('100/100');
    const extra = wrapper.findAll('[data-slot="action-choice-button"]').find(row => row.text().includes('Extra'))!;
    expect(extra.attributes('disabled')).toBeDefined();
    await extra.trigger('click');
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
      { institution: { id: 'b', name: 'Partly' }, recipients: [rep], excluded_recipients: [{ ...rep, id: 'v', skip_reason: 'recent_activity' }], skip_reason: null },
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
  it('limits each person to the inherited scope and prunes selected people when filters change', async () => {
    const institution = (id: string, tenant: number, scope = InstitutionScope.University) => ({
      id, name: id, tenant_id: tenant, tenant_shortname: String(tenant), governance_scope: scope,
    });
    candidates.value = [
      { id: 'u', name: 'Ona', institutions: [institution('a', 1), institution('b', 2)] },
      { id: 'v', name: 'Jonas', institutions: [institution('c', 1, InstitutionScope.Vusa)] },
    ];
    const { wrapper, window } = mountScreen(ActivityRequestPeopleScreen, {
      flow: 'activity.request', activityRequestFilters: { tenantIds: ['1'], governanceScope: InstitutionScope.University },
    });
    expect(wrapper.findAll('[data-slot="action-choice-button"]')).toHaveLength(1);
    await wrapper.get('[data-slot="action-choice-button"]').trigger('click');
    expect(window.draft.activityRequest.people[0]!.institutions.map(item => item.id)).toEqual(['a']);
    await wrapper.get('select').setValue('vusa');
    expect(window.draft.activityRequest.people).toEqual([]);
    expect(wrapper.get('[data-slot="action-choice-button"]').text()).toContain('Jonas');
  });

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
