import { computed, type MaybeRefOrGetter, toValue } from 'vue';

import type {
  AtstovavimasUser,
  AtstovavimasInstitution,
  AtstovavimasGap,
} from '../types';
import { extractCheckInGaps } from '../utils/ganttHelpers';

export function useAtstovavimasData(
  userGetter: MaybeRefOrGetter<AtstovavimasUser>,
  userInstitutionsGetter: MaybeRefOrGetter<AtstovavimasInstitution[]> = [],
) {
  // Secretary institutions may have no duty to join through.
  const institutions = computed<AtstovavimasInstitution[]>(() => {
    const user = toValue(userGetter);
    const userInstitutions = toValue(userInstitutionsGetter) ?? [];
    const institutionsById = new Map(userInstitutions.map(institution => [String(institution.id), institution]));
    const secretaried = userInstitutions.filter(institution => institution.is_secretary ?? institution.is_administered);

    return [
      ...(user?.current_or_upcoming_duties ?? []).map(duty => institutionsById.get(String(duty.institution_id)) ?? null),
      ...secretaried,
    ]
      .filter((institution): institution is AtstovavimasInstitution => institution !== null && institution !== undefined)
      .filter((institution: AtstovavimasInstitution, index: number, self: AtstovavimasInstitution[]) =>
        index === self.findIndex((t: AtstovavimasInstitution) => t && institution && t.id === institution.id),
      );
  });

  const userGaps = computed<AtstovavimasGap[]>(() => extractCheckInGaps(institutions.value));

  return {
    institutions,
    userGaps,
  };
}
