import { defineComponent, h } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import type { ColumnDef } from '@tanstack/vue-table';

import InstitutionStatusSummary from '../InstitutionStatusSummary.vue';
import type { AtstovavimasInstitution, InstitutionStatusSummaryData } from '../../types';

import { commonStubs } from '@/tests/stubs';

let capturedData: AtstovavimasInstitution[] = [];
let capturedColumns: ColumnDef<AtstovavimasInstitution>[] = [];

const SimpleDataTableStub = defineComponent({
  props: {
    data: {
      type: Array,
      required: true,
    },
    columns: {
      type: Array,
      required: true,
    },
  },
  setup(props) {
    capturedData = props.data as AtstovavimasInstitution[];
    capturedColumns = props.columns as ColumnDef<AtstovavimasInstitution>[];

    return () => h('div', { 'data-testid': 'summary-table' });
  },
});

// The chart itself (D3/SVG rendering) is covered by its own component test —
// here we only need to know what data/days it was handed.
const InstitutionStatusTrendChartStub = defineComponent({
  props: {
    data: { type: Array, required: true },
    days: { type: Number, required: true },
    loading: { type: Boolean },
  },
  template: '<div data-testid="trend-chart-stub" />',
});

const loadHistory = vi.fn();

vi.mock('../../Composables/useTenantStatusHistory', () => ({
  useTenantStatusHistory: () => ({
    data: { value: [] },
    isFetching: { value: false },
    loaded: { value: false },
    load: loadHistory,
  }),
}));

function institution(
  id: string,
  status: AtstovavimasInstitution['activity_status']['status'],
  priority: number,
  requiresAction: boolean,
): AtstovavimasInstitution {
  return {
    id,
    name: `Institution ${id}`,
    tenant: { id: '1', shortname: 'VU SA IF' },
    activity_status: {
      status,
      priority,
      requires_action: requiresAction,
      periodicity_days: 30,
      effective_days_since_activity: status === 'no_activity' ? null : 24,
      progress_percentage: status === 'no_activity' ? null : 80,
      last_activity_type: status === 'no_activity' ? null : 'meeting',
      last_activity_at: status === 'no_activity' ? null : '2026-06-02T10:00:00.000Z',
      last_meeting_at: status === 'no_activity' ? null : '2026-06-02T10:00:00.000Z',
      next_meeting_at: null,
      active_check_in_until: null,
    },
  };
}

describe('InstitutionStatusSummary', () => {
  let wrapper: ReturnType<typeof mount>;

  beforeEach(() => {
    loadHistory.mockClear();
  });

  afterEach(() => {
    wrapper?.unmount();
    capturedData = [];
    capturedColumns = [];
  });

  function summary(overrides: Partial<InstitutionStatusSummaryData> = {}): InstitutionStatusSummaryData {
    return {
      all: 0,
      needs_attention: 0,
      overdue: 0,
      approaching: 0,
      no_activity: 0,
      current: 0,
      ...overrides,
    };
  }

  function createWrapper(
    institutions: AtstovavimasInstitution[],
    statusSummary: InstitutionStatusSummaryData,
    loading = false,
    tenantIds: string[] = ['1'],
  ) {
    return mount(InstitutionStatusSummary, {
      props: { institutions, summary: statusSummary, loading, tenantIds },
      global: {
        stubs: {
          ...commonStubs,
          SimpleDataTable: SimpleDataTableStub,
          InstitutionStatusTrendChart: InstitutionStatusTrendChartStub,
        },
      },
    });
  }

  it('renders activity counters from backend statuses', () => {
    wrapper = createWrapper([
      institution('healthy', 'healthy', 0, false),
      institution('approaching', 'approaching', 30, true),
      institution('overdue', 'overdue', 50, true),
      institution('empty', 'no_activity', 40, true),
    ], summary({
      all: 4,
      needs_attention: 3,
      overdue: 1,
      approaching: 1,
      no_activity: 1,
      current: 1,
    }));

    const text = wrapper.text();

    expect(text).toContain('visak.institution_summary.all');
    expect(text).toContain('visak.institution_summary.needs_attention');
    expect(text).toContain('visak.institution_summary.current');
    expect(text).toContain('4');
    expect(text).toContain('3');
  });

  it('passes institutions to the table in backend priority order', async () => {
    wrapper = createWrapper([
      institution('healthy', 'healthy', 0, false),
      institution('approaching', 'approaching', 30, true),
      institution('overdue', 'overdue', 50, true),
    ], summary({ all: 3, needs_attention: 2, overdue: 1, approaching: 1, current: 1 }));

    await wrapper.get('[data-testid="institution-summary-dialog-trigger"]').trigger('click');

    expect(capturedData.map(item => item.id)).toEqual(['overdue', 'approaching', 'healthy']);
    expect(capturedColumns.map(column => column.id ?? ('accessorKey' in column ? column.accessorKey : null)))
      .toContain('activity_reference');
  });

  it('shows the initial loading skeleton before tenant data arrives', () => {
    wrapper = createWrapper([], summary(), true);

    expect(wrapper.find('[data-testid="summary-table"]').exists()).toBe(false);
    expect(wrapper.findAll('.animate-pulse').length).toBeGreaterThan(0);
  });

  // The Trend tab's fetch dedup (skip a refetch for an unchanged tenant/range key)
  // is a property of useTenantStatusHistory itself and is covered by its own
  // composable test; here we only assert when this component decides to call load().
  describe('trend tab', () => {
    it('does not fetch trend data before the Trend tab is opened', () => {
      wrapper = createWrapper([], summary({ all: 1, current: 1 }));

      expect(loadHistory).not.toHaveBeenCalled();
    });

    it('fetches trend data once the Trend tab is opened', async () => {
      wrapper = createWrapper([], summary({ all: 1, current: 1 }), false, ['1']);

      await wrapper.get('[data-testid="institution-summary-trend-tab"]').trigger('mousedown');

      expect(loadHistory).toHaveBeenCalledTimes(1);
      expect(loadHistory).toHaveBeenCalledWith(['1'], 90);
    });

    it('refetches trend data when the selected tenants change while the Trend tab is active', async () => {
      wrapper = createWrapper([], summary({ all: 1, current: 1 }), false, ['1']);

      await wrapper.get('[data-testid="institution-summary-trend-tab"]').trigger('mousedown');
      expect(loadHistory).toHaveBeenCalledTimes(1);

      await wrapper.setProps({ tenantIds: ['2'] });

      expect(loadHistory).toHaveBeenCalledTimes(2);
      expect(loadHistory).toHaveBeenLastCalledWith(['2'], 90);
    });
  });
});
