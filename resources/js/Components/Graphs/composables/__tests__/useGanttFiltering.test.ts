import { describe, expect, it } from 'vitest';
import { computed, ref } from 'vue';

import { useGanttFiltering } from '../useGanttFiltering';

import type { GanttSortBy } from '@/Pages/Admin/Dashboard/Composables/useGanttSettings';

const institutions = [
  { id: 'own', name: 'VU SA Parlamentas', is_internal: true },
  { id: 'external', name: 'VU Senatas', is_internal: false },
];

function build(hideInternal: boolean) {
  const hide = ref(hideInternal);

  const filtering = useGanttFiltering(
    {
      showOnlyWithActivity: () => false,
      showOnlyWithPublicMeetings: () => false,
      hideInternalInstitutions: () => hide.value,
      institutionHasPublicMeetings: () => undefined,
      showDutyMembers: () => false,
    },
    {
      parsedMeetings: computed(() => []),
      parsedGaps: computed(() => []),
      parsedDutyMembers: computed(() => []),
      parsedInactivePeriods: computed(() => []),
      institutions: () => institutions,
      institutionNames: () => undefined,
    },
  );

  return { filtering, hide };
}

describe('useGanttFiltering internal bodies', () => {
  it('draws VU SA\'s own bodies alongside the rest by default', () => {
    expect(build(false).filtering.institutions.value).toEqual(['own', 'external']);
  });

  it('drops them only when the user asks', () => {
    expect(build(true).filtering.institutions.value).toEqual(['external']);
  });

  it('reacts to the toggle without a remount', async () => {
    const { filtering, hide } = build(false);

    hide.value = true;

    expect(filtering.institutions.value).toEqual(['external']);
  });
});

describe('useGanttFiltering sort order', () => {
  const dated = [
    { id: 'b-quiet', name: 'B tyli' },
    { id: 'a-busy', name: 'A aktyvi', last_activity_at: '2026-10-18T10:00:00Z', next_meeting_date: '2026-11-20T10:00:00Z', last_meeting_date: '2026-09-01T10:00:00Z' },
    { id: 'c-soon', name: 'C greit', last_activity_at: '2026-10-01T10:00:00Z', next_meeting_date: '2026-10-21T10:00:00Z', last_meeting_date: '2026-10-10T10:00:00Z' },
    { id: 'd-soon', name: 'D greit', last_activity_at: '2026-10-01T10:00:00Z', next_meeting_date: '2026-10-21T10:00:00Z' },
  ];

  const sorted = (sortBy: GanttSortBy) => useGanttFiltering(
    {
      showOnlyWithActivity: () => false,
      showOnlyWithPublicMeetings: () => false,
      institutionHasPublicMeetings: () => undefined,
      showDutyMembers: () => false,
      sortBy: () => sortBy,
    },
    {
      parsedMeetings: computed(() => []),
      parsedGaps: computed(() => []),
      parsedDutyMembers: computed(() => []),
      parsedInactivePeriods: computed(() => []),
      institutions: () => dated,
      institutionNames: () => undefined,
    },
  ).institutions.value;

  it('sorts by name by default', () => {
    expect(sorted('name')).toEqual(['a-busy', 'b-quiet', 'c-soon', 'd-soon']);
  });

  it('puts the latest activity first, ties by name, rows without a date last', () => {
    expect(sorted('last_activity')).toEqual(['a-busy', 'c-soon', 'd-soon', 'b-quiet']);
  });

  it('puts the soonest next meeting first', () => {
    expect(sorted('next_meeting')).toEqual(['c-soon', 'd-soon', 'a-busy', 'b-quiet']);
  });

  it('puts the most recent past meeting first', () => {
    expect(sorted('last_meeting')).toEqual(['c-soon', 'a-busy', 'b-quiet', 'd-soon']);
  });
});
