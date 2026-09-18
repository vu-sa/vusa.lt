import type { VNodeChild } from 'vue';
import { defineComponent, h, ref } from 'vue';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import ReservationResourceTable from '@/Components/Tables/ReservationResourceTable.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@vueuse/core', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@vueuse/core')>();

  return {
    ...actual,
    useBreakpoints: () => ({ smaller: () => ref(false) }),
  };
});

vi.stubGlobal('route', (name: string) => `/mocked/${name}`);

interface TestRow {
  original: App.Entities.Resource;
}

interface TestColumn {
  id?: string;
  cell?: (context: { row: TestRow }) => VNodeChild;
}

const DataTableStub = defineComponent({
  props: {
    columns: { type: Array<TestColumn>, required: true },
    data: { type: Array<App.Entities.Resource>, required: true },
  },
  setup(props) {
    return () => h('div', props.data.map((original) => {
      const actionColumn = props.columns.find(column => column.id === 'actions');

      return h('div', { class: 'resource-row' }, actionColumn?.cell?.({ row: { original } }));
    }));
  },
});

function reservationWithReversibleResource(): App.Entities.Reservation & { approvable: boolean } {
  const resource = {
    id: 'resource-1',
    name: 'Projector',
    description: null,
    media: [],
    managers: [],
    tenant: { id: 'tenant-1', shortname: 'MIF' },
    pivot: {
      id: 41,
      state: 'lent',
      quantity: 1,
      start_time: '2026-09-20T10:00:00Z',
      end_time: '2026-09-20T12:00:00Z',
      approvable: true,
      approvals: [{ id: 'approval-1', decision: 'approved', reverted_at: null }],
    },
  } as unknown as App.Entities.Resource;

  return {
    id: 'reservation-1',
    resources: [resource],
    approvable: true,
  } as unknown as App.Entities.Reservation & { approvable: boolean };
}

beforeEach(() => {
  vi.mocked(router.post).mockClear();
});

describe('resource backtracking', () => {
  it('submits the selected pivot to the backtrack endpoint after confirmation', async () => {
    const wrapper = mount(ReservationResourceTable, {
      props: { reservation: reservationWithReversibleResource() },
      global: {
        stubs: {
          ...commonStubs,
          DataTable: DataTableStub,
          SpotlightPopover: { template: '<div><slot /></div>' },
          ReservationBulkActionBar: true,
          ReservationResourceCard: true,
          Textarea: {
            props: ['modelValue'],
            emits: ['update:modelValue'],
            template: '<textarea :value="modelValue" />',
          },
        },
      },
    });

    await wrapper.get('button[title="reservations.actions.backtrack"]').trigger('click');
    const confirm = wrapper.findAll('[role="dialog"] button')
      .find(button => button.text().includes('reservations.actions.backtrack'));

    expect(confirm).toBeDefined();
    await confirm!.trigger('click');

    expect(router.post).toHaveBeenCalledWith(
      '/mocked/approvals.backtrack',
      expect.objectContaining({
        approvable_type: 'reservation_resource',
        approvable_ids: ['41'],
      }),
      expect.any(Object),
    );
  });
});
