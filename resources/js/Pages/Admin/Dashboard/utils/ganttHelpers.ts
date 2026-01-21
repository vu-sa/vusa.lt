/**
 * Shared helper functions for Gantt chart data transformations.
 * Used by UserTimelineSection, TenantTimelineSection, and FullscreenGanttModal.
 */
import type {
  AtstovavimosInstitution,
  GanttDutyMember,
  InactivePeriod,
  GanttMeeting,
  GanttInstitution,
} from '../types';

/**
 * Extract duty members from institutions for Gantt display.
 * Processes all users (including historical) from institution duties.
 */
export function extractDutyMembers(institutions: AtstovavimosInstitution[]): GanttDutyMember[] {
  const members: GanttDutyMember[] = [];
  
  for (const institution of institutions) {
    const inst = institution as any;
    for (const duty of (inst.duties ?? [])) {
      // Use users (all members including historical) for Gantt timeline display
      for (const user of (duty.users ?? [])) {
        const pivot = user.pivot ?? {};
        if (!pivot.start_date) continue;
        
        members.push({
          institution_id: String(institution.id),
          duty_id: String(duty.id),
          user: {
            id: String(user.id),
            name: String(user.name ?? ''),
            profile_photo_path: user.profile_photo_path ?? null
          },
          start_date: new Date(pivot.start_date),
          end_date: pivot.end_date ? new Date(pivot.end_date) : null
        });
      }
    }
  }
  
  return members;
}

/**
 * Calculate inactive periods for institutions based on duty member coverage.
 * Finds gaps where no duty member was active.
 */
export function calculateInactivePeriods(
  institutions: AtstovavimosInstitution[],
  members: GanttDutyMember[]
): InactivePeriod[] {
  const periods: InactivePeriod[] = [];
  
  for (const institution of institutions) {
    const instId = String(institution.id);
    const instMembers = members.filter(m => m.institution_id === instId);
    
    if (instMembers.length === 0) continue;
    
    const sortedMembers = [...instMembers].sort((a, b) => 
      a.start_date.getTime() - b.start_date.getTime()
    );
    
    const activePeriods: Array<{ from: Date; until: Date }> = [];
    
    for (const member of sortedMembers) {
      const from = member.start_date;
      const until = member.end_date ?? new Date();
      
      if (activePeriods.length === 0) {
        activePeriods.push({ from, until });
      } else {
        const last = activePeriods[activePeriods.length - 1]!;
        if (from <= last.until) {
          if (until > last.until) {
            last.until = until;
          }
        } else {
          periods.push({
            institution_id: instId,
            from: last.until,
            until: from
          });
          activePeriods.push({ from, until });
        }
      }
    }
  }
  
  return periods;
}

/**
 * Extract meetings from related institutions with agenda items for tooltip display.
 * Returns formatted GanttMeeting array.
 */
export function extractMeetingsFromInstitutions(
  institutions: AtstovavimosInstitution[]
): GanttMeeting[] {
  return institutions.flatMap(inst => 
    ((inst as any).meetings ?? []).map((m: any) => {
      // Check authorization for agenda item visibility
      const isAuthorized = (inst as any).authorized !== false;
      const agendaItems = isAuthorized 
        ? (m.agenda_items ?? []).slice(0, 4).map((item: any) => ({
            id: String(item.id),
            title: String(item.title ?? ''),
            student_vote: item.student_vote ?? null,
            decision: item.decision ?? null,
          }))
        : [];
      const totalAgendaCount = isAuthorized ? (m.agenda_items ?? []).length : 0;

      return {
        id: m.id,
        start_time: new Date(m.start_time),
        institution_id: inst.id,
        institution: getInstitutionDisplayName(inst),
        completion_status: m.completion_status,
        agenda_items: agendaItems,
        agenda_items_count: totalAgendaCount,
        authorized: isAuthorized,
        has_report: m.has_report,
        has_protocol: m.has_protocol,
        // Extract meeting type for icon differentiation (in-person, remote, email)
        type_slug: m.type ?? m.type_slug,
      };
    })
  );
}

/**
 * Format institutions for Gantt component display.
 */
export function formatInstitutionsForGantt(
  institutions: AtstovavimosInstitution[],
  isRelated = false
): GanttInstitution[] {
  return institutions.map(i => ({
    id: i.id,
    name: getInstitutionDisplayName(i),
    tenant_id: i.tenant?.id,
    is_related: isRelated || i.is_related,
    relationship_direction: i.relationship_direction,
    source_institution_id: i.source_institution_id,
    authorized: i.authorized
  }));
}

/**
 * Get display name for an institution, handling various name formats.
 */
export function getInstitutionDisplayName(institution: AtstovavimosInstitution | any): string {
  return String(
    institution?.name?.lt ?? 
    institution?.name?.en ?? 
    institution?.name ?? 
    institution?.shortname ?? 
    institution?.id ?? ''
  );
}

/**
 * Build institution names lookup map.
 */
export function buildInstitutionNamesMap(
  institutions: AtstovavimosInstitution[]
): Record<string, string> {
  const result: Record<string, string> = {};
  for (const inst of institutions) {
    result[String(inst.id)] = getInstitutionDisplayName(inst);
  }
  return result;
}

/**
 * Build institution tenant ID lookup map.
 */
export function buildInstitutionTenantMap(
  institutions: AtstovavimosInstitution[]
): Record<string, string> {
  const result: Record<string, string> = {};
  for (const inst of institutions) {
    result[String(inst.id)] = String(inst.tenant?.id ?? '');
  }
  return result;
}

/**
 * Build institution has public meetings lookup map.
 */
export function buildInstitutionPublicMeetingsMap(
  institutions: AtstovavimosInstitution[]
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
  institutions: AtstovavimosInstitution[]
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
  additions: Record<string, T>
): Record<string, T> {
  return { ...base, ...additions };
}
