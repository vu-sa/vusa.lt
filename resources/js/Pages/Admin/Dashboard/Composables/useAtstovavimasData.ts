import { computed, type MaybeRefOrGetter, toValue } from 'vue';

import type {
  AtstovavimasUser,
  AtstovavimasInstitution,
  InstitutionInsights,
  AtstovavimasGap,
} from '../types';

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

  // User gaps derived from all check-ins for each institution
  const userGaps = computed<AtstovavimasGap[]>(() => {
    return institutions.value.flatMap((inst: AtstovavimasInstitution) => {
      return (inst.check_ins ?? []).map(checkIn => ({
        institution_id: inst.id,
        from: new Date(`${checkIn.start_date}T00:00:00`),
        until: new Date(`${checkIn.end_date}T00:00:00`),
        mode: 'no_meetings', // All check-ins represent "no meetings"
        note: checkIn.note || undefined,
      }));
    });
  });

  // Calculate institutions insights for footer information
  const institutionsInsights = computed<InstitutionInsights>(() => {
    return {
      attention: institutions.value
        .filter(institution => institution.activity_status.requires_action)
        .sort((a, b) => b.activity_status.priority - a.activity_status.priority)
        .slice(0, 2)
        .map(institution => ({
          id: institution.id,
          name: institution.name,
          ...institution.activity_status,
        })),
    };
  });

  return {
    institutions,
    userGaps,
    institutionsInsights,
  };
}
