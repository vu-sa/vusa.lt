import { describe, expect, it } from 'vitest';
import { ref } from 'vue';

import { useUserTimelineData } from '../useUserTimelineData';
import type { AtstovavimasInstitution } from '../../types';

function institution(
  id: string,
  tenantId: string,
  sourceInstitutionId?: string,
): AtstovavimasInstitution {
  return {
    id,
    name: id,
    tenant_id: tenantId,
    tenant: {
      id: tenantId,
      shortname: `Tenant ${tenantId}`,
    },
    source_institution_id: sourceInstitutionId,
  } as AtstovavimasInstitution;
}

describe('useUserTimelineData', () => {
  it('keeps cross-tenant institutions related to the selected direct institution', () => {
    const directInstitution = institution('vu-senatas', '16');
    const relatedCouncil = institution('vu-kf-taryba', '8', directInstitution.id);
    const unrelatedCouncil = institution('vu-if-taryba', '1', 'other-source');

    const timeline = useUserTimelineData({
      institutions: ref([directInstitution]),
      meetings: ref([]),
      gaps: ref([]),
      relatedInstitutions: ref([relatedCouncil, unrelatedCouncil]),
      showRelatedInstitutions: ref(true),
    });

    expect(timeline.mergedInstitutions.value.map(item => item.id))
      .toEqual(['vu-senatas', 'vu-kf-taryba']);
    expect(timeline.mergedInstitutions.value.find(item => item.id === 'vu-kf-taryba')?.tenant_id).toBe('8');
  });

  it('draws the check-ins of related institutions while they are shown', () => {
    const directInstitution = institution('vu-senatas', '16');
    const relatedCouncil = {
      ...institution('vu-kf-taryba', '8', directInstitution.id),
      check_ins: [{ id: 'c1', institution_id: 'vu-kf-taryba', start_date: '2026-03-01', end_date: '2026-03-31', note: null }],
    } as unknown as AtstovavimasInstitution;
    const shown = ref(true);

    const timeline = useUserTimelineData({
      institutions: ref([directInstitution]),
      meetings: ref([]),
      gaps: ref([]),
      relatedInstitutions: ref([relatedCouncil]),
      showRelatedInstitutions: shown,
    });

    expect(timeline.mergedGaps.value.map(gap => [gap.institution_id, gap.from])).toEqual([['vu-kf-taryba', new Date(2026, 2, 1)]]);
    shown.value = false;
    expect(timeline.mergedGaps.value).toEqual([]);
  });
});
