import { describe, expect, it, vi } from 'vitest';
import { computed } from 'vue';

import { useGanttLabels } from '../useGanttLabels';

import type { GanttSortBy } from '@/Pages/Admin/Dashboard/Composables/useGanttSettings';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const institutions = [
  {
    id: 'senatas',
    last_activity_at: '2026-10-12T10:00:00Z',
    last_meeting_date: '2026-10-01T10:00:00Z',
    next_meeting_date: '2026-10-28T10:00:00Z',
  },
  { id: 'taryba', last_meeting_date: '2026-09-01T10:00:00Z' },
  { id: 'nothing' },
];

const gaps = [
  { institution_id: 'taryba', untilDate: new Date('2026-10-10T00:00:00Z') },
  { institution_id: 'taryba', untilDate: new Date('2026-08-10T00:00:00Z') },
  { institution_id: 'senatas', untilDate: new Date('2026-11-30T00:00:00Z') },
];

function rowDate(sortBy: GanttSortBy, id: string) {
  const { rowDateFor } = useGanttLabels(
    { institutionNames: () => undefined, tenantNames: () => undefined, sortBy: () => sortBy },
    { institutions: () => institutions, filteredMeetings: computed(() => []), filteredGaps: computed(() => gaps) },
  );
  const result = rowDateFor(id);

  return result && { kind: result.kind, date: result.date.toISOString().slice(0, 10) };
}

describe('useGanttLabels row date', () => {
  it('shows the date the rows are sorted by', () => {
    expect(rowDate('last_activity', 'senatas')).toEqual({ kind: 'last_activity', date: '2026-10-12' });
    expect(rowDate('next_meeting', 'senatas')).toEqual({ kind: 'next_meeting', date: '2026-10-28' });
    expect(rowDate('last_meeting', 'senatas')).toEqual({ kind: 'last_meeting', date: '2026-10-01' });
    expect(rowDate('next_meeting', 'taryba')).toBeNull();
  });

  it('sorted by name, shows the latest of the meetings and check-in ends', () => {
    expect(rowDate('name', 'senatas')).toEqual({ kind: 'check_in_end', date: '2026-11-30' });
    expect(rowDate('name', 'taryba')).toEqual({ kind: 'check_in_end', date: '2026-10-10' });
    expect(rowDate('name', 'nothing')).toBeNull();
  });
});
