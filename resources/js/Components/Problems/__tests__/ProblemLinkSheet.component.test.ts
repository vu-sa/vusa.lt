import { flushPromises, mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { computed, ref } from 'vue';

import ProblemLinkSheet from '@/Components/Problems/ProblemLinkSheet.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name: string, id?: string) => `/mocked-route/${name}${id ? `/${id}` : ''}`);

const items = ref([
  { id: 'linked', title: { lt: 'Jau susieta', en: 'Already linked' }, status: 'open', tenant: null },
  { id: 'free', title: { lt: 'Laisva problema', en: 'Free problem' }, status: 'in_progress', tenant: { shortname: 'VU SA MIF' } },
]);
const requestedUrls: string[] = [];

vi.mock('@/Composables/useApi', () => ({
  useApi: vi.fn((url: { value: string }) => ({
    data: computed(() => ({ items: items.value })),
    isFetching: ref(false),
    execute: vi.fn(async () => {
      requestedUrls.push(url.value);
    }),
  })),
}));

const SheetStub = { props: ['open'], template: '<div v-if="open"><slot /></div>' };

const mountSheet = () => mount(ProblemLinkSheet, {
  props: { open: true, agendaItemId: 'item-1', linkedIds: ['linked'] },
  global: {
    stubs: {
      ...commonStubs,
      Sheet: SheetStub,
      SheetContent: { template: '<div><slot /></div>' },
      SheetHeader: { template: '<div><slot /></div>' },
      SheetTitle: { template: '<h2><slot /></h2>' },
      SheetDescription: { template: '<p><slot /></p>' },
    },
  },
});

describe('ProblemLinkSheet.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    requestedUrls.length = 0;
  });

  it('asks only for unresolved problems and leaves out the ones already linked', async () => {
    const wrapper = mountSheet();
    await flushPromises();

    const filters = new URL(requestedUrls[0] ?? '', 'http://test').searchParams.get('filters');
    expect(JSON.parse(filters ?? '{}')).toEqual({ status: ['open', 'in_progress'] });

    const options = wrapper.findAll('[data-testid="problem-link-option"]');
    expect(options).toHaveLength(1);
    expect(options[0]?.text()).toContain('Laisva problema');
  });

  it('links the chosen problem to the agenda item', async () => {
    const wrapper = mountSheet();
    await flushPromises();

    await wrapper.find('[data-testid="problem-link-option"]').trigger('click');

    expect(router.post).toHaveBeenCalledWith(
      '/mocked-route/agendaItems.problems.store/item-1',
      { problem_id: 'free' },
      expect.objectContaining({ preserveScroll: true }),
    );
  });
});
