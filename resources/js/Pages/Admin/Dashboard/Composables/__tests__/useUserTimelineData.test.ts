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
      relatedInstitutions: ref([relatedCouncil, unrelatedCouncil]),
      showRelatedInstitutions: ref(true),
    });

    expect(timeline.mergedInstitutions.value.map(item => item.id))
      .toEqual(['vu-senatas', 'vu-kf-taryba']);
    expect(timeline.mergedInstitutionTenant.value['vu-kf-taryba']).toBe('8');
  });
});
