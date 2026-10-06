import { describe, expect, it } from 'vitest';
import { computed, ref } from 'vue';

import { useGanttChartData } from '../useGanttChartData';
import type { AtstovavimasInstitution } from '../../types';

describe('useGanttChartData', () => {
  it('keeps is_internal on formatted tenant institutions', () => {
    const institutions = ref([
      { id: 'a', name: 'VU SA Parlamentas', tenant_id: '1', is_internal: true },
      { id: 'b', name: 'VU Senatas', tenant_id: '1', is_internal: false },
    ] as unknown as AtstovavimasInstitution[]);

    const { formattedTenantInstitutions } = useGanttChartData(institutions, [], computed(() => []));

    expect(formattedTenantInstitutions.value.map(i => i.is_internal)).toEqual([true, false]);
  });
});
