/**
 * Composable for calculating institution urgency levels
 *
 * Analyzes institution data to determine urgency for:
 * - Member fill rate (positions filled vs available)
 * - Meeting status (last meeting relative to periodicity)
 * - Duty fill rate (duties with members vs total duties)
 * - Overall institution urgency
 */

import { computed, type ComputedRef, type MaybeRefOrGetter, toValue } from 'vue';

import type { UrgencyLevel } from '@/Composables/useDashboardCardStyles';
import type { InstitutionActivityStatus } from '@/Types/InstitutionActivity';

interface InstitutionData {
  id: string | number;
  name?: string;
  meeting_periodicity_days?: number | null;
  activity_status?: InstitutionActivityStatus;
  current_users?: unknown[];
  duties?: Array<{
    id: string | number;
    places_to_occupy?: number | null;
    current_users?: unknown[];
  }>;
  meetings?: Array<{
    id: string | number;
    start_time: string;
  }>;
}

interface InstitutionUrgencyResult {
  /** Urgency based on member fill rate */
  memberUrgency: ComputedRef<UrgencyLevel>;

  /** Urgency based on meeting periodicity status */
  meetingUrgency: ComputedRef<UrgencyLevel>;

  /** Urgency based on duty fill rate */
  dutyUrgency: ComputedRef<UrgencyLevel>;

  /** Overall institution urgency (worst of all metrics) */
  overallUrgency: ComputedRef<UrgencyLevel>;

  /** Effective days since the latest meeting or completed activity report */
  effectiveDaysSinceActivity: ComputedRef<number | null>;

  /** Whether the institution is overdue for a meeting */
  isOverdue: ComputedRef<boolean>;

  /** Member fill percentage (0-100) */
  memberFillRate: ComputedRef<number>;

  /** Duty fill percentage (0-100) */
  dutyFillRate: ComputedRef<number>;

  /** Total positions available */
  totalPositions: ComputedRef<number>;

  /** Positions currently filled */
  filledPositions: ComputedRef<number>;

  /** Last meeting date */
  lastMeeting: ComputedRef<{ id: string | number; start_time: string } | null>;
}

/**
 * Calculate urgency levels for an institution
 *
 * @param institution - Institution data (can be ref, getter, or plain object)
 * @returns Object with computed urgency levels and related metrics
 *
 * @example
 * ```ts
 * const { overallUrgency, memberUrgency, isOverdue } = useInstitutionUrgency(props.institution)
 * ```
 */
export function useInstitutionUrgency(
  institution: MaybeRefOrGetter<InstitutionData>,
): InstitutionUrgencyResult {
  // Calculate total positions from duties
  const totalPositions = computed(() => {
    const inst = toValue(institution);
    return inst.duties?.reduce((sum, duty) => {
      return sum + (duty.places_to_occupy || 0);
    }, 0) || 0;
  });

  // Calculate filled positions
  const filledPositions = computed(() => {
    const inst = toValue(institution);
    return inst.current_users?.length || 0;
  });

  // Member fill rate percentage
  const memberFillRate = computed(() => {
    if (totalPositions.value === 0) return 100;
    return Math.round((filledPositions.value / totalPositions.value) * 100);
  });

  // Find last meeting
  const lastMeeting = computed(() => {
    const inst = toValue(institution);
    const lastMeetingAt = inst.activity_status?.last_meeting_at;
    if (!lastMeetingAt) return null;

    return inst.meetings?.find(
      meeting => new Date(meeting.start_time).getTime() === new Date(lastMeetingAt).getTime(),
    ) ?? {
      id: 'last-meeting',
      start_time: lastMeetingAt,
    };
  });

  const effectiveDaysSinceActivity = computed(() => {
    return toValue(institution).activity_status?.effective_days_since_activity ?? null;
  });

  // Check if overdue based on periodicity
  const isOverdue = computed(() => {
    return toValue(institution).activity_status?.status === 'overdue';
  });

  // Duty fill rate (duties with at least one member)
  const dutyFillRate = computed(() => {
    const inst = toValue(institution);
    const duties = inst.duties || [];
    if (duties.length === 0) return 100;

    const filledDuties = duties.filter(
      duty => (duty.current_users?.length || 0) > 0,
    ).length;

    return Math.round((filledDuties / duties.length) * 100);
  });

  // Member urgency based on fill rate
  const memberUrgency = computed<UrgencyLevel>(() => {
    const rate = memberFillRate.value;
    if (rate >= 80) return 'success';
    if (rate >= 50) return 'warning';
    if (rate > 0) return 'danger';
    return 'neutral';
  });

  // Meeting urgency based on periodicity
  const meetingUrgency = computed<UrgencyLevel>(() => {
    return matchActivityUrgency(toValue(institution).activity_status?.status);
  });

  // Duty urgency based on fill rate
  const dutyUrgency = computed<UrgencyLevel>(() => {
    const rate = dutyFillRate.value;
    if (rate >= 80) return 'success';
    if (rate >= 50) return 'warning';
    if (rate > 0) return 'danger';
    return 'neutral';
  });

  // Overall urgency (worst of all metrics)
  const overallUrgency = computed<UrgencyLevel>(() => {
    const urgencies: UrgencyLevel[] = [
      memberUrgency.value,
      meetingUrgency.value,
      dutyUrgency.value,
    ];

    // Priority: danger > warning > neutral > success
    if (urgencies.includes('danger')) return 'danger';
    if (urgencies.includes('warning')) return 'warning';
    if (urgencies.includes('neutral')) return 'neutral';
    return 'success';
  });

  return {
    memberUrgency,
    meetingUrgency,
    dutyUrgency,
    overallUrgency,
    effectiveDaysSinceActivity,
    isOverdue,
    memberFillRate,
    dutyFillRate,
    totalPositions,
    filledPositions,
    lastMeeting,
  };
}

function matchActivityUrgency(
  status: InstitutionActivityStatus['status'] | undefined,
): UrgencyLevel {
  if (status === 'overdue') return 'danger';
  if (status === 'approaching') return 'warning';
  if (status === 'no_activity' || !status) return 'neutral';
  return 'success';
}
