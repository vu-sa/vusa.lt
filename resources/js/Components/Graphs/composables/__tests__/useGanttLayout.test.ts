import { describe, expect, it } from 'vitest';
import { computed, ref } from 'vue';

import { useGanttLayout, type GanttInstitutionMeta } from '../useGanttLayout';
import type { GanttGroupBy } from '@/Pages/Admin/Dashboard/Composables/useGanttSettings';

const institutions: GanttInstitutionMeta[] = [
  { id: 'senatas', tenant_id: 1, types: [{ id: 't-council', title: 'Taryba' }, { id: 't-senate', title: 'Senatas ir jo komitetai' }] },
  { id: 'kolegija', tenant_id: 2, types: [{ id: 't-council', title: 'Taryba' }] },
  { id: 'untyped', tenant_id: 2, types: [] },
];

function build(groupBy: GanttGroupBy) {
  return useGanttLayout(
    { groupBy: ref(groupBy), height: () => undefined },
    {
      institutions: computed(() => institutions.map(i => i.id)),
      institutionsMeta: () => institutions,
      tenantNames: computed(() => ({ 1: 'VU SA', 2: 'VU SA MIF' })),
    },
  );
}

const outline = (layout: ReturnType<typeof build>) =>
  layout.layoutRows.value.map(row => (row.type === 'group' ? `# ${row.label}` : String(row.institutionId)));

describe('useGanttLayout', () => {
  it('sections by padalinys', () => {
    expect(outline(build('tenant'))).toEqual(['# VU SA', 'senatas', '# VU SA MIF', 'kolegija', 'untyped']);
  });

  it('sections by type, repeating an institution under each of its types and putting the untyped last', () => {
    const layout = build('type');

    expect(outline(layout)).toEqual(['# Senatas ir jo komitetai', 'senatas', '# Taryba', 'senatas', 'kolegija', '# Be tipo', 'untyped']);
    expect(layout.rowKeysByInstitution.value.get('senatas')).toHaveLength(2);
    expect(new Set(layout.layoutRows.value.map(row => row.key)).size).toBe(layout.layoutRows.value.length);
  });

  it('lists every institution once, without headers, when ungrouped', () => {
    const layout = build('none');

    expect(outline(layout)).toEqual(['senatas', 'kolegija', 'untyped']);
    expect(layout.layoutRows.value.map(row => row.top)).toEqual([0, 28, 56]);
  });
});

describe('useGanttLayout upcoming sections', () => {
  // Wednesday 2026-10-14; this week ends Sunday the 18th, next week the 25th.
  const now = new Date(2026, 9, 14, 12);
  const upcoming: GanttInstitutionMeta[] = [
    { id: 'later', next_meeting_date: new Date(2026, 10, 3).toISOString() },
    { id: 'nothing' },
    { id: 'this-week', next_meeting_date: new Date(2026, 9, 18, 23).toISOString() },
    { id: 'checked-in', has_check_ins: true },
    { id: 'next-week', next_meeting_date: new Date(2026, 9, 19, 9).toISOString() },
    { id: 'this-month', next_meeting_date: new Date(2026, 9, 28).toISOString() },
    { id: 'met-before', last_meeting_date: new Date(2026, 8, 1).toISOString() },
  ];

  const outlineAt = (at: Date) => outline(useGanttLayout(
    { groupBy: ref<GanttGroupBy>('upcoming'), height: () => undefined, now: () => at },
    {
      institutions: computed(() => upcoming.map(i => i.id)),
      institutionsMeta: () => upcoming,
      tenantNames: computed(() => ({})),
    },
  ));

  it('sections by when the next meeting is, in time order, then past activity and nothing', () => {
    expect(outlineAt(now)).toEqual([
      '# visak.gantt.upcoming_groups.this_week', 'this-week',
      '# visak.gantt.upcoming_groups.next_week', 'next-week',
      '# visak.gantt.upcoming_groups.this_month', 'this-month',
      '# visak.gantt.upcoming_groups.later', 'later',
      '# visak.gantt.upcoming_groups.past', 'checked-in', 'met-before',
      '# visak.gantt.upcoming_groups.no_info', 'nothing',
    ]);
  });

  it('skips "this month" when next week already reaches into the next month', () => {
    const lateInMonth = outlineAt(new Date(2026, 9, 26, 12));

    expect(lateInMonth).not.toContain('# visak.gantt.upcoming_groups.this_month');
    // Monday the 26th: next week runs 2–8 November.
    expect(lateInMonth.slice(lateInMonth.indexOf('# visak.gantt.upcoming_groups.next_week'), -1).slice(0, 3))
      .toEqual(['# visak.gantt.upcoming_groups.next_week', 'later', '# visak.gantt.upcoming_groups.past']);
  });
});
