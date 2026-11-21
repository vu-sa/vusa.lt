/**
 * Shared helper functions for Gantt chart data transformations.
 * Used by UserTimelineSection and TenantTimelineSection.
 */
import { getOutcomeVotes } from '@/Composables/useAgendaItemStyling';

import type {
  AtstovavimasInstitution,
  GanttDutyMember,
  InactivePeriod,
  GanttMeeting,
  GanttInstitution,
  GanttInstitutionType,
} from '../types';

/**
 * Extract duty members from institutions for Gantt display.
 * Processes all users (including historical) from institution duties.
 */
export function extractDutyMembers(institutions: AtstovavimasInstitution[]): GanttDutyMember[] {
  const members: GanttDutyMember[] = [];

  for (const institution of institutions) {
    for (const period of (institution.membership_periods ?? [])) {
      members.push({
        institution_id: String(institution.id),
        duty_id: String(period.duty_id),
        user: {
          id: String(period.user.id),
          name: period.user.name,
          profile_photo_path: period.user.profile_photo_path,
          activityCategory: institution.member_activity_categories?.[String(period.user.id)],
        },
        start_date: new Date(period.start_date),
        end_date: period.end_date ? new Date(period.end_date) : null,
      });
    }

  }

  return members;
}

/**
 * Calculate inactive periods for institutions based on duty member coverage.
 * Finds gaps where no duty member was active.
 */
export function calculateInactivePeriods(
  institutions: AtstovavimasInstitution[],
  members: GanttDutyMember[],
): InactivePeriod[] {
  const periods: InactivePeriod[] = [];
  const membersByInstitution = new Map<string, GanttDutyMember[]>();
  for (const member of members) {
    const list = membersByInstitution.get(member.institution_id) ?? [];
    list.push(member);
    membersByInstitution.set(member.institution_id, list);
  }

  for (const institution of institutions) {
    const instId = String(institution.id);
    const instMembers = membersByInstitution.get(instId) ?? [];

    if (instMembers.length === 0) continue;

    const sortedMembers = [...instMembers].sort((a, b) =>
      a.start_date.getTime() - b.start_date.getTime(),
    );

    const activePeriods: Array<{ from: Date; until: Date }> = [];

    for (const member of sortedMembers) {
      const from = member.start_date;
      const until = member.end_date ?? new Date();

      if (activePeriods.length === 0) {
        activePeriods.push({ from, until });
      }
      else {
        const last = activePeriods[activePeriods.length - 1]!;
        if (from <= last.until) {
          if (until > last.until) {
            last.until = until;
          }
        }
        else {
          periods.push({
            institution_id: instId,
            from: last.until,
            until: from,
          });
          activePeriods.push({ from, until });
        }
      }
    }
  }

  return periods;
}

/**
 * Format institutions for Gantt component display.
 */
export function formatInstitutionsForGantt(
  institutions: AtstovavimasInstitution[],
  isRelated = false,
): GanttInstitution[] {
  return institutions.map(i => ({
    id: i.id,
    name: getInstitutionDisplayName(i),
    tenant_id: i.tenant?.id === undefined ? undefined : String(i.tenant.id),
    types: ganttInstitutionTypes(i),
    is_internal: i.is_internal,
    ...ganttActivityDates(i),
    is_related: isRelated || i.is_related,
    relationship_direction: i.relationship_direction,
    source_institution_id: i.source_institution_id,
    authorized: i.authorized,
  }));
}

/** What the Gantt sorts and sections rows by; server-derived, so independent of the loaded meeting windows. */
export function ganttActivityDates(institution: AtstovavimasInstitution): Pick<GanttInstitution, 'last_meeting_date' | 'next_meeting_date' | 'last_activity_at' | 'has_check_ins'> {
  return {
    last_meeting_date: institution.last_meeting_date ?? null,
    next_meeting_date: institution.next_meeting_date ?? null,
    last_activity_at: institution.last_activity_at ?? null,
    has_check_ins: (institution.check_ins?.length ?? 0) > 0,
  };
}

export function ganttInstitutionTypes(institution: AtstovavimasInstitution): GanttInstitutionType[] {
  return (institution.types ?? []).map(type => ({
    id: String(type.id),
    title: typeof type.title === 'string' ? type.title : getInstitutionDisplayName({ name: type.title }),
  }));
}

/**
 * Get display name for an institution, handling various name formats.
 */
export function getInstitutionDisplayName(institution: AtstovavimasInstitution | any): string {
  return String(
    institution?.name?.lt
    ?? institution?.name?.en
    ?? institution?.name
    ?? institution?.shortname
    ?? institution?.id ?? '',
  );
}

/**
 * Build institution names lookup map.
 */
export function buildInstitutionNamesMap(
  institutions: AtstovavimasInstitution[],
): Record<string, string> {
  const result: Record<string, string> = {};
  for (const inst of institutions) {
    result[String(inst.id)] = getInstitutionDisplayName(inst);
  }
  return result;
}

/**
 * Build institution has public meetings lookup map.
 */
export function buildInstitutionPublicMeetingsMap(
  institutions: AtstovavimasInstitution[],
): Record<string, boolean> {
  const result: Record<string, boolean> = {};
  for (const inst of institutions) {
    result[String(inst.id)] = Boolean(inst.has_public_meetings);
  }
  return result;
}

/**
 * Build institution periodicity lookup map.
 */
export function buildInstitutionPeriodicityMap(
  institutions: AtstovavimasInstitution[],
): Record<string, number> {
  const result: Record<string, number> = {};
  for (const inst of institutions) {
    result[String(inst.id)] = (inst as any).meeting_periodicity_days ?? 30;
  }
  return result;
}

/**
 * Merge two record maps, with second taking precedence.
 */
export function mergeRecordMaps<T>(
  base: Record<string, T>,
  additions: Record<string, T>,
): Record<string, T> {
  return { ...base, ...additions };
}
