import { describe, expect, it } from 'vitest';
import { computed, ref } from 'vue';

import { useGanttFiltering } from '../useGanttFiltering';

const institutions = [
  { id: 'own', name: 'VU SA Parlamentas', is_internal: true },
  { id: 'external', name: 'VU Senatas', is_internal: false },
];

function build(hideInternal: boolean) {
  const hide = ref(hideInternal);

  const filtering = useGanttFiltering(
    {
      tenantFilter: () => undefined,
      institutionTenant: () => undefined,
      showOnlyWithActivity: () => false,
      showOnlyWithPublicMeetings: () => false,
      hideInternalInstitutions: () => hide.value,
      institutionHasPublicMeetings: () => undefined,
      institutionsOrder: () => undefined,
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
