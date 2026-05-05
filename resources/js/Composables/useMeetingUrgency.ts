/**
 * Composable for calculating meeting urgency levels
 *
 * Analyzes meeting data to determine urgency for:
 * - Agenda completion (items with decisions)
 * - Document status (protocol and report uploaded)
 * - Task status (pending tasks)
 * - Overall meeting urgency
 */

import { computed, type ComputedRef, type MaybeRefOrGetter, toValue } from 'vue';

import type { UrgencyLevel } from '@/Composables/useDashboardCardStyles';

interface MeetingData {
  id: string | number;
  start_time: string;
  has_protocol?: boolean;
  has_report?: boolean;
  agenda_items?: Array<{
    id: string | number;
    decision?: 'positive' | 'negative' | 'neutral' | null;
  }>;
  tasks?: Array<{
    id: string | number;
    completed_at?: string | null;
  }>;
  files?: any[];
}

interface MeetingUrgencyResult {
  /** Urgency based on agenda item completion */
  agendaUrgency: ComputedRef<UrgencyLevel>;

  /** Urgency based on document upload status */
  documentUrgency: ComputedRef<UrgencyLevel>;

  /** Urgency based on pending tasks */
  taskUrgency: ComputedRef<UrgencyLevel>;

  /** Overall meeting urgency (worst of all metrics) */
  overallUrgency: ComputedRef<UrgencyLevel>;

  /** Whether the meeting is in the past */
  isPastMeeting: ComputedRef<boolean>;

  /** Days since the meeting (negative if future) */
  daysSinceMeeting: ComputedRef<number>;

  /** Agenda completion percentage (0-100) */
  agendaCompletion: ComputedRef<number>;

  /** Number of completed agenda items */
  completedAgendaItems: ComputedRef<number>;

  /** Total agenda items */
  totalAgendaItems: ComputedRef<number>;

  /** Number of pending tasks */
  pendingTasksCount: ComputedRef<number>;

  /** Total tasks */
  totalTasksCount: ComputedRef<number>;

  /** Whether protocol is uploaded */
  hasProtocol: ComputedRef<boolean>;

  /** Whether report is uploaded */
  hasReport: ComputedRef<boolean>;

  /** Number of attached files */
  filesCount: ComputedRef<number>;
}

/**
 * Calculate urgency levels for a meeting
 *
 * @param meeting - Meeting data (can be ref, getter, or plain object)
 * @returns Object with computed urgency levels and related metrics
 *
 * @example
 * ```ts
 * const { overallUrgency, documentUrgency, pendingTasksCount } = useMeetingUrgency(props.meeting)
 * ```
 */
export function useMeetingUrgency(
  meeting: MaybeRefOrGetter<MeetingData>,
): MeetingUrgencyResult {
  // Meeting timing
  const daysSinceMeeting = computed(() => {
    const mtg = toValue(meeting);
    const meetingDate = new Date(mtg.start_time);
    const now = new Date();
    return Math.floor((now.getTime() - meetingDate.getTime()) / (1000 * 60 * 60 * 24));
  });

  const isPastMeeting = computed(() => daysSinceMeeting.value > 0);

  // Agenda metrics
  const totalAgendaItems = computed(() => {
    const mtg = toValue(meeting);
    return mtg.agenda_items?.length ?? 0;
  });

  const completedAgendaItems = computed(() => {
    const mtg = toValue(meeting);
    return mtg.agenda_items?.filter(item =>
      item.decision === 'positive'
      || item.decision === 'negative'
      || item.decision === 'neutral',
    ).length ?? 0;
  });

  const agendaCompletion = computed(() => {
    if (totalAgendaItems.value === 0) return 100;
    return Math.round((completedAgendaItems.value / totalAgendaItems.value) * 100);
  });

  // Task metrics
  const totalTasksCount = computed(() => {
    const mtg = toValue(meeting);
    return mtg.tasks?.length ?? 0;
  });

  const pendingTasksCount = computed(() => {
    const mtg = toValue(meeting);
    return mtg.tasks?.filter(task => !task.completed_at).length ?? 0;
  });

  // Document metrics
  const hasProtocol = computed(() => {
    const mtg = toValue(meeting);
    return mtg.has_protocol ?? false;
  });

  const hasReport = computed(() => {
    const mtg = toValue(meeting);
    return mtg.has_report ?? false;
  });

  const filesCount = computed(() => {
    const mtg = toValue(meeting);
    return mtg.files?.length ?? 0;
  });

  // Agenda urgency
  const agendaUrgency = computed<UrgencyLevel>(() => {
    // If no agenda items, neutral
    if (totalAgendaItems.value === 0) return 'neutral';

    const completion = agendaCompletion.value;
    if (completion >= 100) return 'success';
    if (completion >= 50) return 'warning';
    return 'danger';
  });

  // Document urgency (only matters for past meetings)
  const documentUrgency = computed<UrgencyLevel>(() => {
    // For future meetings, documents aren't expected yet
    if (!isPastMeeting.value) return 'neutral';

    const protocol = hasProtocol.value;
    const report = hasReport.value;

    if (protocol && report) return 'success';
    if (protocol || report) return 'warning';

    // More urgent if meeting was a while ago
    if (daysSinceMeeting.value > 7) return 'danger';
    return 'warning';
  });

  // Task urgency
  const taskUrgency = computed<UrgencyLevel>(() => {
    // If no tasks, neutral
    if (totalTasksCount.value === 0) return 'neutral';

    const pending = pendingTasksCount.value;
    if (pending === 0) return 'success';

    // More urgent based on ratio and time since meeting
    const completionRate = 1 - (pending / totalTasksCount.value);

    if (completionRate >= 0.8) return 'success';
    if (completionRate >= 0.5) return 'warning';
    return 'danger';
  });

  // Overall urgency (considers meeting timing)
  const overallUrgency = computed<UrgencyLevel>(() => {
    // For future meetings, be more lenient
    if (!isPastMeeting.value) {
      // Only agenda matters for upcoming meetings
      return agendaUrgency.value === 'neutral' ? 'neutral' : agendaUrgency.value;
    }

    // For past meetings, check all metrics
    const urgencies: UrgencyLevel[] = [
      agendaUrgency.value,
      documentUrgency.value,
      taskUrgency.value,
    ];

    // Priority: danger > warning > neutral > success
    if (urgencies.includes('danger')) return 'danger';
    if (urgencies.includes('warning')) return 'warning';
    if (urgencies.every(u => u === 'success')) return 'success';
    return 'neutral';
  });

  return {
    agendaUrgency,
    documentUrgency,
    taskUrgency,
    overallUrgency,
    isPastMeeting,
    daysSinceMeeting,
    agendaCompletion,
    completedAgendaItems,
    totalAgendaItems,
    pendingTasksCount,
    totalTasksCount,
    hasProtocol,
    hasReport,
    filesCount,
  };
}
