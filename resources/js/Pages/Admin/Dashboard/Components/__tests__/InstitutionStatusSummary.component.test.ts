import { defineComponent, h } from 'vue';
import { afterEach, describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import type { ColumnDef } from '@tanstack/vue-table';

import InstitutionStatusSummary from '../InstitutionStatusSummary.vue';
import type { AtstovavimosInstitution, InstitutionStatusSummaryData } from '../../types';

import { commonStubs } from '@/tests/stubs';

let capturedData: AtstovavimosInstitution[] = [];
let capturedColumns: ColumnDef<AtstovavimosInstitution>[] = [];

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
    capturedData = props.data as AtstovavimosInstitution[];
    capturedColumns = props.columns as ColumnDef<AtstovavimosInstitution>[];

    return () => h('div', { 'data-testid': 'summary-table' });
  },
});

function institution(
  id: string,
  status: AtstovavimosInstitution['activity_status']['status'],
  priority: number,
  requiresAction: boolean,
): AtstovavimosInstitution {
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
    institutions: AtstovavimosInstitution[],
    statusSummary: InstitutionStatusSummaryData,
    loading = false,
  ) {
    return mount(InstitutionStatusSummary, {
      props: { institutions, summary: statusSummary, loading },
      global: {
        stubs: {
          ...commonStubs,
          SimpleDataTable: SimpleDataTableStub,
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
});
