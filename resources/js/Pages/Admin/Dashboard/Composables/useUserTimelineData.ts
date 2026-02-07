/**
 * Composable for managing user timeline data with related institutions merging.
 * Centralizes the logic for combining user's direct institutions with related institutions.
 */
import { computed, type Ref, type ComputedRef } from 'vue';
import type {
  AtstovavimosInstitution,
  GanttMeeting,
  GanttInstitution,
  GanttDutyMember,
  InactivePeriod,
} from '../types';
import {
  extractDutyMembers,
  calculateInactivePeriods,
  extractMeetingsFromInstitutions,
  formatInstitutionsForGantt,
  buildInstitutionNamesMap,
  buildInstitutionTenantMap,
  buildInstitutionPublicMeetingsMap,
  buildInstitutionPeriodicityMap,
  mergeRecordMaps,
} from '../utils/ganttHelpers';

interface UseUserTimelineDataOptions {
  /** User's direct institutions */
  institutions: Ref<AtstovavimosInstitution[]> | ComputedRef<AtstovavimosInstitution[]>;
  /** User's direct meetings */
  meetings: Ref<GanttMeeting[]> | ComputedRef<GanttMeeting[]>;
  /** Related institutions (lazy-loaded) */
  relatedInstitutions: Ref<AtstovavimosInstitution[]> | ComputedRef<AtstovavimosInstitution[]>;
  /** Whether to show related institutions */
  showRelatedInstitutions: Ref<boolean>;
  /** Base duty members from user's institutions */
  baseDutyMembers?: Ref<GanttDutyMember[]> | ComputedRef<GanttDutyMember[]>;
  /** Base inactive periods from user's institutions */
  baseInactivePeriods?: Ref<InactivePeriod[]> | ComputedRef<InactivePeriod[]>;
  /** Base institution names map */
  baseInstitutionNames?: Ref<Record<string, string>> | ComputedRef<Record<string, string>>;
  /** Base institution tenant map */
  baseInstitutionTenant?: Ref<Record<string, string>> | ComputedRef<Record<string, string>>;
  /** Base has public meetings map */
  baseInstitutionHasPublicMeetings?: Ref<Record<string, boolean>> | ComputedRef<Record<string, boolean>>;
  /** Base institution periodicity map */
  baseInstitutionPeriodicity?: Ref<Record<string, number>> | ComputedRef<Record<string, number>>;
}

export function useUserTimelineData(options: UseUserTimelineDataOptions) {
  const {
    institutions,
    meetings,
    relatedInstitutions,
    showRelatedInstitutions,
    baseDutyMembers,
    baseInactivePeriods,
    baseInstitutionNames,
    baseInstitutionTenant,
    baseInstitutionHasPublicMeetings,
    baseInstitutionPeriodicity,
  } = options;

  // Merged institutions: user's + related (when enabled)
  const mergedInstitutions = computed<GanttInstitution[]>(() => {
    const userFormatted = formatInstitutionsForGantt(institutions.value, false);

    if (!showRelatedInstitutions.value || !relatedInstitutions.value?.length) {
      return userFormatted;
    }

    const relatedFormatted = formatInstitutionsForGantt(relatedInstitutions.value, true);
    return [...userFormatted, ...relatedFormatted];
  });

  // Merged meetings: user's + extracted from related institutions
  const mergedMeetings = computed<GanttMeeting[]>(() => {
    if (!showRelatedInstitutions.value || !relatedInstitutions.value?.length) {
      return meetings.value;
    }

    const relatedMeetings = extractMeetingsFromInstitutions(relatedInstitutions.value);
    return [...meetings.value, ...relatedMeetings];
  });

  // Merged duty members
  const mergedDutyMembers = computed<GanttDutyMember[]>(() => {
    const base = baseDutyMembers?.value ?? [];

    if (!showRelatedInstitutions.value || !relatedInstitutions.value?.length) {
      return base;
    }

    const relatedMembers = extractDutyMembers(relatedInstitutions.value);
    return [...base, ...relatedMembers];
  });

  // Merged inactive periods
  const mergedInactivePeriods = computed<InactivePeriod[]>(() => {
    const base = baseInactivePeriods?.value ?? [];

    if (!showRelatedInstitutions.value || !relatedInstitutions.value?.length) {
      return base;
    }

    const relatedMembers = extractDutyMembers(relatedInstitutions.value);
    const relatedPeriods = calculateInactivePeriods(relatedInstitutions.value, relatedMembers);
    return [...base, ...relatedPeriods];
  });

  // Merged institution names map
  const mergedInstitutionNames = computed<Record<string, string>>(() => {
    const base = baseInstitutionNames?.value ?? buildInstitutionNamesMap(institutions.value);

    if (!showRelatedInstitutions.value || !relatedInstitutions.value?.length) {
      return base;
    }

    const relatedNames = buildInstitutionNamesMap(relatedInstitutions.value);
    return mergeRecordMaps(base, relatedNames);
  });

  // Merged institution tenant map
  const mergedInstitutionTenant = computed<Record<string, string>>(() => {
    const base = baseInstitutionTenant?.value ?? buildInstitutionTenantMap(institutions.value);

    if (!showRelatedInstitutions.value || !relatedInstitutions.value?.length) {
      return base;
    }

    const relatedTenants = buildInstitutionTenantMap(relatedInstitutions.value);
    return mergeRecordMaps(base, relatedTenants);
  });

  // Merged has public meetings map
  const mergedInstitutionHasPublicMeetings = computed<Record<string, boolean>>(() => {
    const base = baseInstitutionHasPublicMeetings?.value ?? buildInstitutionPublicMeetingsMap(institutions.value);

    if (!showRelatedInstitutions.value || !relatedInstitutions.value?.length) {
      return base;
    }

    const relatedPublic = buildInstitutionPublicMeetingsMap(relatedInstitutions.value);
    return mergeRecordMaps(base, relatedPublic);
  });

  // Merged institution periodicity map
  const mergedInstitutionPeriodicity = computed<Record<string, number>>(() => {
    const base = baseInstitutionPeriodicity?.value ?? buildInstitutionPeriodicityMap(institutions.value);

    if (!showRelatedInstitutions.value || !relatedInstitutions.value?.length) {
      return base;
    }

    const relatedPeriodicity = buildInstitutionPeriodicityMap(relatedInstitutions.value);
    return mergeRecordMaps(base, relatedPeriodicity);
  });

  return {
    mergedInstitutions,
    mergedMeetings,
    mergedDutyMembers,
    mergedInactivePeriods,
    mergedInstitutionNames,
    mergedInstitutionTenant,
    mergedInstitutionHasPublicMeetings,
    mergedInstitutionPeriodicity,
  };
}
