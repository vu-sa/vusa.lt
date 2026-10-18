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
