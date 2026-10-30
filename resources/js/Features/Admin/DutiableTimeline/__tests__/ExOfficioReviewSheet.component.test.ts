import { computed, reactive, ref } from 'vue';
import { flushPromises, mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import * as api from '@/Composables/useApi';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';
import ExOfficioReviewSheet from '../ExOfficioReviewSheet.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const makeItem = (id: string, extra = {}) => ({
  id, kind: 'link', holder_id: 'person', holder_name: 'Vardas', duty_name: 'Pareigybė',
  before: { start_date: '2024-01-01', end_date: null },
  after: { start_date: '2024-01-01', end_date: '2024-12-31' },
  selected: true, orphan: false, access_change: false, quota_change: false,
  tenant_before: null, tenant_after: null, remainders: [], survivors: [], requires: [], ...extra,
});
const plan = { fingerprint: 'fingerprint', items: [
  makeItem('safe'), makeItem('orphan', { orphan: true, selected: false, access_change: true }),
  makeItem('duplicate', { kind: 'remove_duplicate', selected: false }),
], blocked: [] };

const stubs = {
  ...commonStubs,
  SheetForm: {
    props: ['open', 'disabled'], emits: ['submit'],
    template: '<form v-if="open" @submit.prevent="$emit(\'submit\')"><slot /><button type="submit" :disabled="disabled">Save</button></form>',
  },
  AccessChangeWarningDialog: {
    props: ['open'], emits: ['confirm', 'cancel'],
    template: '<div v-if="open" data-testid="access-warning"><button @click="$emit(\'confirm\')">Confirm</button></div>',
  },
};

function mountSheet() {
  return mount(ExOfficioReviewSheet, { props: { open: true, dutyId: 'duty' }, global: { stubs } });
}

describe('ExOfficioReviewSheet', () => {
  beforeEach(() => {
    vi.mocked(router.post).mockClear();
    vi.mocked(usePage).mockReturnValue(reactive(createMockPage()));
    vi.spyOn(api, 'useApi').mockReturnValue({
      data: computed(() => plan), response: ref(null), error: computed(() => null),
      isFetching: ref(false), isFinished: ref(true), isSuccess: computed(() => true),
      execute: vi.fn().mockResolvedValue(undefined), abort: vi.fn(),
    });
  });
  afterEach(() => { vi.restoreAllMocks(); });

  it('preselects safe proposals and submits only those through the guard', async () => {
    const wrapper = mountSheet();
    await flushPromises();
    expect(wrapper.findAll('[role="checkbox"]').map(box => box.attributes('aria-checked'))).toEqual(['true', 'false', 'false']);
    await wrapper.get('form').trigger('submit');
    expect(router.post).toHaveBeenCalledWith(expect.anything(), expect.objectContaining({
      item_ids: ['safe'], fingerprint: 'fingerprint', remainder_choices: {}, acknowledge_access_change: false,
    }), expect.anything());
    wrapper.unmount();
  });

  it('defaults orphan remainders to end and sends an explicit keep choice', async () => {
    const wrapper = mountSheet();
    await flushPromises();
    expect(wrapper.findAll('[role="radio"]')[0].attributes('aria-checked')).toBe('true');
    await wrapper.findAll('[role="checkbox"]')[1].trigger('click');
    await wrapper.findAll('[role="radio"]')[1].trigger('click');
    await wrapper.get('form').trigger('submit');
    expect(router.post).toHaveBeenCalledWith(expect.anything(), expect.objectContaining({
      item_ids: ['safe', 'orphan'], remainder_choices: { orphan: 'keep' },
    }), expect.anything());
    wrapper.unmount();
  });

  it('replays a guarded submit only after the access warning is confirmed', async () => {
    const page = reactive(createMockPage());
    vi.mocked(usePage).mockReturnValue(page);
    const wrapper = mountSheet();
    await flushPromises();
    await wrapper.get('form').trigger('submit');
    Object.assign(page.props.flash, { access_change_warning: { lostRoles: ['Koordinatorius'], severity: 'warning' } });
    await flushPromises();
    await wrapper.get('[data-testid="access-warning"] button').trigger('click');
    expect(router.post).toHaveBeenLastCalledWith(expect.anything(), expect.objectContaining({ acknowledge_access_change: true }), expect.anything());
    wrapper.unmount();
  });

  it('shows an empty state and prevents submitting an empty selection', async () => {
    vi.spyOn(api, 'useApi').mockReturnValue({
      data: computed(() => ({ ...plan, items: [] })), response: ref(null), error: computed(() => null),
      isFetching: ref(false), isFinished: ref(true), isSuccess: computed(() => true),
      execute: vi.fn().mockResolvedValue(undefined), abort: vi.fn(),
    });
    const wrapper = mountSheet();
    await flushPromises();
    expect(wrapper.text()).toContain('dutiables.ex_officio_review.empty');
    expect(wrapper.get('button[type="submit"]').attributes('disabled')).toBeDefined();
    wrapper.unmount();
  });
});
