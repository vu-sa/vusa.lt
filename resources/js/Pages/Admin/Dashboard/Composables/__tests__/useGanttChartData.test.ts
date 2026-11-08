import { describe, expect, it } from 'vitest';
import { computed, ref } from 'vue';

import { useGanttChartData } from '../useGanttChartData';
import type { AtstovavimasInstitution } from '../../types';

describe('useGanttChartData', () => {
  it('places check-in date-only values at local calendar midnight', () => {
    const institutions = ref([{
      id: 'a',
      check_ins: [{ start_date: '2026-03-15', end_date: '2026-03-29' }],
    }] as unknown as AtstovavimasInstitution[]);
    const { tenantGaps } = useGanttChartData(institutions, [], computed(() => []));

    expect(tenantGaps.value[0]!.from).toEqual(new Date(2026, 2, 15));
    expect(tenantGaps.value[0]!.until).toEqual(new Date(2026, 2, 29));
  });

  it('keeps is_internal on formatted tenant institutions', () => {
    const institutions = ref([
      { id: 'a', name: 'VU SA Parlamentas', tenant_id: '1', is_internal: true },
      { id: 'b', name: 'VU Senatas', tenant_id: '1', is_internal: false },
    ] as unknown as AtstovavimasInstitution[]);

    const { formattedTenantInstitutions } = useGanttChartData(institutions, [], computed(() => []));

    expect(formattedTenantInstitutions.value.map(i => i.is_internal)).toEqual([true, false]);
  });
});
