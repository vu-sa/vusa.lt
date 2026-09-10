import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { router } from '@inertiajs/vue3';

import ShowSupportRequests from '@/Pages/Admin/Dashboard/ShowSupportRequests.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name: string) => `/mocked/${name}`);

const request = {
  id: 'support-1',
  support_service_id: 1,
  support_request_type_id: 1,
  support_request_area_id: 1,
  visibility: 'private' as const,
  status: 'new' as const,
  title: 'Prisijungimo klaida',
  description: 'Negaliu prisijungti.',
  created_at: '2026-09-10T10:00:00Z',
  creator: { id: 'user-1', name: 'Justinas Kavoliūnas' },
  type: { id: 1, name: 'Klaida' },
  area: { id: 1, name: 'Svetainė' },
};

const createWrapper = () => mount(ShowSupportRequests, {
  props: {
    requests: {
      data: [request], total: 1, per_page: 20, current_page: 1, last_page: 1, from: 1, to: 1,
    },
    currentTab: 'all',
    tabCounts: { all: 4, mine: 1 },
    statusCounts: { new: 2, reviewing: 1, planned: 0, in_progress: 1, done: 0, declined: 0 },
    filters: {},
    sorting: [{ id: 'created_at', desc: true }],
    types: [{ id: 1, name: 'Klaida' }],
    areas: [{ id: 1, name: 'Svetainė' }],
    assignees: [],
    statusOptions: [
      { value: 'new', label: 'Naujas', badgeVariant: 'sky' },
      { value: 'reviewing', label: 'Peržiūrima', badgeVariant: 'warning' },
      { value: 'planned', label: 'Suplanuota', badgeVariant: 'zinc' },
      { value: 'in_progress', label: 'Vykdoma', badgeVariant: 'amber' },
      { value: 'done', label: 'Išspręsta', badgeVariant: 'success' },
      { value: 'declined', label: 'Atmesta', badgeVariant: 'destructive' },
    ],
  },
  global: { stubs: { ...commonStubs } },
});

beforeEach(() => {
  vi.mocked(router.get).mockClear();
});

describe('support request dashboard', () => {
  it('starts on all and renders the request table', () => {
    const wrapper = createWrapper();

    expect(wrapper.find('[role="tab"][data-state="active"]').text()).toContain('Visi');
    expect(wrapper.text()).toContain('Prisijungimo klaida');
    expect(wrapper.text()).toContain('Justinas Kavoliūnas');
  });

  it('applies a status KPI as a server-side filter', async () => {
    const wrapper = createWrapper();
    const newTile = wrapper.findAll('button').find(button => button.text().includes('Naujas'));

    await newTile!.trigger('click');
    await nextTick();

    expect(router.get).toHaveBeenCalledWith(
      '/mocked/mySupportRequests.index',
      expect.objectContaining({ tab: 'all', page: 1, filters: JSON.stringify({ status: 'new' }) }),
      expect.any(Object),
    );
  });
});
