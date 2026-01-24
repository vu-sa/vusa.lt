import { trans as $t } from 'laravel-vue-i18n';
import {
  CheckCircle,
  XCircle,
  MinusCircle,
  CircleDashed,
  Clock,
  Info,
  HelpCircle,
  type LucideIcon,
} from 'lucide-vue-next';

// ============================================================================
// Type Definitions (exported for component usage)
// ============================================================================

export type VoteValue = 'positive' | 'negative' | 'neutral' | string | null | undefined;
export type AgendaItemType = 'voting' | 'informational' | 'deferred' | string | null | undefined;

export interface Vote {
  id?: string | number;
  is_main?: boolean;
  decision?: VoteValue;
  student_vote?: VoteValue;
  student_benefit?: VoteValue;
  title?: string | null;
}

export interface AgendaItem {
  id: string | number;
  title?: string;
  type?: AgendaItemType;
  votes?: Vote[];
  main_vote?: Vote | null;
}

/**
 * Agenda item status types - 7 distinct statuses for display
 *
 * Used for both admin and public views to consistently represent
 * the state of an agenda item based on its type and main vote.
 */
export type AgendaItemStatus
  = 'student_aligned' // Voting: student_vote === decision (green)
    | 'student_misaligned' // Voting: student_vote !== decision (red/amber)
    | 'neutral_decided' // Voting: decision is neutral (gray)
    | 'no_vote' // Voting type but no vote recorded yet (amber/warning)
    | 'deferred' // Type is deferred (gray muted)
    | 'informational' // Type is informational (gray)
    | 'unset'; // Type is null/undefined - needs attention (amber/warning)

/**
 * Status metadata for consistent display across components
 */
export interface AgendaItemStatusMeta {
  status: AgendaItemStatus;
  icon: LucideIcon;
  label: string;
  colorClass: string;
  bgClass: string;
  borderClass: string;
  dotClass: string;
}

/**
 * Calculate the status of an agenda item based on its type and main vote
 * This is the primary function for determining agenda item display state.
 *
 * @param item - The agenda item (can include votes array or main_vote directly)
 * @returns The calculated status
 */
export function getAgendaItemStatus(item: AgendaItem): AgendaItemStatus {
  // No type set - needs attention
  if (item.type === null || item.type === undefined) {
    return 'unset';
  }

  // Non-voting types
  if (item.type === 'deferred') {
    return 'deferred';
  }

  if (item.type === 'informational') {
    return 'informational';
  }

  // Voting type - check main vote
  const mainVote = getMainVote(item);

  // No vote recorded yet
  if (!mainVote?.decision) {
    return 'no_vote';
  }

  // Neutral decision
  if (mainVote.decision === 'neutral') {
    return 'neutral_decided';
  }

  // Check student vote alignment
  if (mainVote.student_vote) {
    if (mainVote.student_vote === mainVote.decision) {
      return 'student_aligned';
    }
    return 'student_misaligned';
  }

  // Has decision but no student vote recorded - treat as incomplete
  return 'no_vote';
}

/**
 * Get full status metadata for an agenda item
 * Returns icon, label, and styling classes for the status
 */
export function getAgendaItemStatusMeta(item: AgendaItem): AgendaItemStatusMeta {
  const status = getAgendaItemStatus(item);

  const statusMap: Record<AgendaItemStatus, AgendaItemStatusMeta> = {
    student_aligned: {
      status: 'student_aligned',
      icon: CheckCircle,
      label: $t('Studentų pozicija priimta'),
      colorClass: 'text-emerald-600 dark:text-emerald-400',
      bgClass: 'bg-emerald-100 dark:bg-emerald-900/30',
      borderClass: 'border-emerald-200 dark:border-emerald-800',
      dotClass: 'bg-emerald-500',
    },
    student_misaligned: {
      status: 'student_misaligned',
      icon: XCircle,
      label: $t('Studentų pozicija nesutampa su sprendimu'),
      colorClass: 'text-red-600 dark:text-red-400',
      bgClass: 'bg-red-100 dark:bg-red-900/30',
      borderClass: 'border-red-200 dark:border-red-800',
      dotClass: 'bg-red-500',
    },
    neutral_decided: {
      status: 'neutral_decided',
      icon: MinusCircle,
      label: $t('Neutralus sprendimas'),
      colorClass: 'text-zinc-600 dark:text-zinc-400',
      bgClass: 'bg-zinc-100 dark:bg-zinc-800',
      borderClass: 'border-zinc-200 dark:border-zinc-700',
      dotClass: 'bg-zinc-400',
    },
    no_vote: {
      status: 'no_vote',
      icon: CircleDashed,
      label: $t('Neaptartas'),
      colorClass: 'text-amber-600 dark:text-amber-400',
      bgClass: 'bg-amber-100 dark:bg-amber-900/30',
      borderClass: 'border-amber-200 dark:border-amber-800',
      dotClass: 'bg-amber-500',
    },
    deferred: {
      status: 'deferred',
      icon: Clock,
      label: $t('Atidėtas'),
      colorClass: 'text-zinc-400 dark:text-zinc-500',
      bgClass: 'bg-zinc-100 dark:bg-zinc-800',
      borderClass: 'border-zinc-200 dark:border-zinc-700',
      dotClass: 'bg-zinc-300 dark:bg-zinc-600',
    },
    informational: {
      status: 'informational',
      icon: Info,
      label: $t('Informacinis'),
      colorClass: 'text-zinc-500 dark:text-zinc-400',
      bgClass: 'bg-zinc-100 dark:bg-zinc-800',
      borderClass: 'border-zinc-200 dark:border-zinc-700',
      dotClass: 'bg-zinc-400',
    },
    unset: {
      status: 'unset',
      icon: HelpCircle,
      label: $t('Nepažymėtas'),
      colorClass: 'text-amber-600 dark:text-amber-400',
      bgClass: 'bg-amber-100 dark:bg-amber-900/30',
      borderClass: 'border-amber-200 dark:border-amber-800',
      dotClass: 'bg-amber-500',
    },
  };

  return statusMap[status];
}

/**
 * Calculate meeting-level status summary from agenda items
 * Used for Gantt chart and meeting list displays
 */
export interface MeetingStatusSummary {
  totalItems: number;
  aligned: number;
  misaligned: number;
  neutralDecided: number;
  noVote: number;
  deferred: number;
  informational: number;
  unset: number;
  /** Overall status for coloring: 'complete' | 'incomplete' | 'empty' */
  overallStatus: 'complete' | 'incomplete' | 'empty';
  /** Vote alignment status: 'all_match' | 'mixed' | 'all_mismatch' | 'neutral' | 'unknown' */
  voteAlignmentStatus: 'all_match' | 'mixed' | 'all_mismatch' | 'neutral' | 'unknown';
  /** Completion rate (0-100) for items that should have votes */
  completionRate: number;
  /** Alignment rate (0-100) for items with both student vote and decision */
  alignmentRate: number;
}

/**
 * Calculate meeting status summary from agenda items
 * @param items - Array of agenda items (with votes or main_vote loaded)
 */
export function getMeetingStatusSummary(items: AgendaItem[]): MeetingStatusSummary {
  const summary: MeetingStatusSummary = {
    totalItems: items.length,
    aligned: 0,
    misaligned: 0,
    neutralDecided: 0,
    noVote: 0,
    deferred: 0,
    informational: 0,
    unset: 0,
    overallStatus: 'empty',
    voteAlignmentStatus: 'unknown',
    completionRate: 0,
    alignmentRate: 0,
  };

  if (items.length === 0) {
    return summary;
  }

  for (const item of items) {
    const status = getAgendaItemStatus(item);
    switch (status) {
      case 'student_aligned':
        summary.aligned++;
        break;
      case 'student_misaligned':
        summary.misaligned++;
        break;
      case 'neutral_decided':
        summary.neutralDecided++;
        break;
      case 'no_vote':
        summary.noVote++;
        break;
      case 'deferred':
        summary.deferred++;
        break;
      case 'informational':
        summary.informational++;
        break;
      case 'unset':
        summary.unset++;
        break;
    }
  }

  // Calculate completion rate (for voting items only)
  const votingItems = summary.aligned + summary.misaligned + summary.neutralDecided + summary.noVote;
  const completedVotingItems = summary.aligned + summary.misaligned + summary.neutralDecided;

  if (votingItems > 0) {
    summary.completionRate = Math.round((completedVotingItems / votingItems) * 100);
  }

  // Calculate alignment rate (for items with both student vote and decision)
  const alignableItems = summary.aligned + summary.misaligned;
  if (alignableItems > 0) {
    summary.alignmentRate = Math.round((summary.aligned / alignableItems) * 100);
  }

  // Determine overall status
  if (summary.unset > 0 || summary.noVote > 0) {
    summary.overallStatus = 'incomplete';
  }
  else if (summary.totalItems > 0) {
    summary.overallStatus = 'complete';
  }

  // Determine vote alignment status (reuse alignableItems from above)
  if (alignableItems === 0) {
    // No voting items with decisions - check if neutral
    if (summary.neutralDecided > 0) {
      summary.voteAlignmentStatus = 'neutral';
    }
    // else stays 'unknown'
  }
  else if (summary.misaligned === 0) {
    summary.voteAlignmentStatus = 'all_match';
  }
  else if (summary.aligned === 0) {
    summary.voteAlignmentStatus = 'all_mismatch';
  }
  else {
    summary.voteAlignmentStatus = 'mixed';
  }

  return summary;
}

/**
 * Get the main vote from an agenda item
 * Supports both direct main_vote property and votes array lookup
 */
export function getMainVote(item: AgendaItem): Vote | undefined {
  // Prefer direct main_vote property if available
  if (item.main_vote) {
    return item.main_vote;
  }
  // Fall back to finding in votes array
  return item.votes?.find(v => v.is_main);
}

/**
 * Get number badge class based on item type and vote status
 * Used for the numbered badge next to agenda item titles
 */
export function getNumberBadgeClass(item: AgendaItem): string {
  // Items without type set - amber/warning (needs attention)
  if (item.type === null || item.type === undefined) {
    return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
  }

  // Deferred items - muted gray
  if (item.type === 'deferred') {
    return 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500';
  }

  // Informational items - neutral gray
  if (item.type === 'informational') {
    return 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400';
  }

  // Voting items - check vote status
  const mainVote = getMainVote(item);

  if (!mainVote?.decision) {
    // Not yet discussed - amber/warning
    return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
  }

  // Has a decision
  if (mainVote.decision === 'positive') {
    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
  }
  if (mainVote.decision === 'negative') {
    return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
  }

  // Neutral decision
  return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300';
}

/**
 * Get status text for an agenda item
 */
export function getStatusText(item: AgendaItem): string {
  // If type is not set, item needs type selection
  if (item.type === null || item.type === undefined) return $t('Nepažymėtas');

  if (item.type === 'informational') return $t('Informacinis');
  if (item.type === 'deferred') return $t('Atidėtas');

  const mainVote = getMainVote(item);

  if (!mainVote?.decision) return $t('Neaptartas');

  if (mainVote.decision === 'positive') return $t('Priimtas');
  if (mainVote.decision === 'negative') return $t('Atmestas');
  return $t('Neutralus');
}

/**
 * Get status icon (checkmark, X, or empty)
 */
export function getStatusIcon(item: AgendaItem): string {
  if (item.type === 'informational' || item.type === 'deferred') return '';

  const mainVote = getMainVote(item);

  if (!mainVote?.decision) return '';
  if (mainVote.decision === 'positive') return '✓';
  if (mainVote.decision === 'negative') return '✗';
  return '';
}

/**
 * Get status text class for coloring
 */
export function getStatusTextClass(item: AgendaItem): string {
  // Unset type - amber/warning color
  if (item.type === null || item.type === undefined) return 'text-amber-600 dark:text-amber-400';

  if (item.type === 'informational') return 'text-zinc-500 dark:text-zinc-400';
  if (item.type === 'deferred') return 'text-zinc-400 dark:text-zinc-500';

  const mainVote = getMainVote(item);

  if (!mainVote?.decision) return 'text-zinc-400 dark:text-zinc-500';

  if (mainVote.decision === 'positive') return 'text-emerald-600 dark:text-emerald-400';
  if (mainVote.decision === 'negative') return 'text-red-600 dark:text-red-400';
  return 'text-zinc-500 dark:text-zinc-400';
}

/**
 * Get student vote label
 */
export function getStudentVoteLabel(studentVote: VoteValue): string {
  switch (studentVote) {
    case 'positive': return $t('Pritarė');
    case 'negative': return $t('Nepritarė');
    case 'neutral': return $t('Susilaikyta');
    default: return '';
  }
}

/**
 * Get short student vote indicator
 */
export function getStudentVoteShort(studentVote: VoteValue): string {
  switch (studentVote) {
    case 'positive': return '+';
    case 'negative': return '-';
    case 'neutral': return '0';
    default: return '?';
  }
}

/**
 * Get vote decision label
 */
export function getDecisionLabel(decision: VoteValue): string {
  switch (decision) {
    case 'positive': return $t('Priimtas');
    case 'negative': return $t('Nepriimtas');
    case 'neutral': return $t('Neutralus');
    default: return $t('Neaptartas');
  }
}

/**
 * Get vote badge class based on decision and student vote alignment
 */
export function getVoteBadgeClass(vote: Vote): string {
  const { decision, student_vote } = vote;

  // If no decision, show neutral styling
  if (!decision) {
    return 'bg-zinc-100 text-zinc-600 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700';
  }

  // Check alignment
  const aligned = decision === student_vote;

  if (decision === 'positive') {
    return aligned
      ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800'
      : 'bg-emerald-50 text-emerald-700 border-red-300 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-red-700';
  }

  if (decision === 'negative') {
    return aligned
      ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800'
      : 'bg-red-50 text-red-700 border-emerald-300 dark:bg-red-900/30 dark:text-red-400 dark:border-emerald-700';
  }

  // Neutral
  return 'bg-zinc-100 text-zinc-600 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700';
}

/**
 * Get student vote alignment class (for showing if student vote matches decision)
 */
export function getStudentVoteAlignmentClass(vote: Vote): string {
  const { student_vote, decision } = vote;

  // Show alignment color when both are set
  if (decision && student_vote) {
    if (decision === student_vote) {
      return 'text-emerald-600 dark:text-emerald-400';
    }
    return 'text-red-600 dark:text-red-400';
  }

  // Otherwise just show student vote color
  switch (student_vote) {
    case 'positive': return 'text-emerald-600 dark:text-emerald-400';
    case 'negative': return 'text-red-600 dark:text-red-400';
    case 'neutral': return 'text-zinc-500 dark:text-zinc-400';
    default: return 'text-zinc-400 dark:text-zinc-500';
  }
}

/**
 * Get background color class based on student_benefit value
 * Used for vote badges to indicate the perceived benefit of the decision
 */
export function getStudentBenefitBgClass(benefit: VoteValue): string {
  switch (benefit) {
    case 'positive':
      return 'bg-emerald-50 dark:bg-emerald-950/40';
    case 'negative':
      return 'bg-red-50 dark:bg-red-950/40';
    case 'neutral':
      return 'bg-zinc-100 dark:bg-zinc-800';
    default:
      return 'bg-zinc-100 dark:bg-zinc-800';
  }
}

/**
 * Get decision text color that works on student_benefit background
 * Ensures text is readable regardless of background color
 */
export function getDecisionTextColorClass(decision: VoteValue): string {
  switch (decision) {
    case 'positive':
      return 'text-emerald-700 dark:text-emerald-300';
    case 'negative':
      return 'text-red-700 dark:text-red-300';
    case 'neutral':
      return 'text-zinc-700 dark:text-zinc-300';
    default:
      return 'text-zinc-700 dark:text-zinc-300';
  }
}

/**
 * Get icon color for student vote that contrasts with background
 * Ensures icon stands out regardless of student_benefit background
 */
export function getStudentVoteIconClass(studentVote: VoteValue): string {
  // Always use strong colors that contrast with any background
  switch (studentVote) {
    case 'positive':
      return 'text-emerald-600 dark:text-emerald-400';
    case 'negative':
      return 'text-red-600 dark:text-red-400';
    case 'neutral':
      return 'text-zinc-600 dark:text-zinc-400';
    default:
      return 'text-zinc-400 dark:text-zinc-500';
  }
}

/**
 * Get vote status color dot class
 */
export function getVoteStatusDotClass(vote: Vote): string {
  if (!vote.decision && !vote.student_vote) {
    return 'bg-zinc-300 dark:bg-zinc-600';
  }
  if (vote.decision === 'positive') {
    return 'bg-emerald-500';
  }
  if (vote.decision === 'negative') {
    return 'bg-red-500';
  }
  return 'bg-zinc-400';
}

// ============================================================================
// Shared Vote Display Utilities (for both admin and public components)
// ============================================================================

/**
 * Get text color class for a vote value (positive/negative/neutral)
 * Used by both VoteStatusIndicator (public) and VoteSelectionBadge (admin)
 */
export function getVoteTextColorClass(value: VoteValue): string {
  switch (value) {
    case 'positive':
      return 'text-green-600 dark:text-green-400';
    case 'negative':
      return 'text-red-600 dark:text-red-400';
    case 'neutral':
      return 'text-zinc-600 dark:text-zinc-400';
    default:
      return 'text-zinc-300 dark:text-zinc-500'; // No data
  }
}

/**
 * Get background color class for a vote value
 * Used for vote selection buttons and badges
 */
export function getVoteBgColorClass(value: VoteValue, isSelected = false): string {
  if (!isSelected) {
    return 'bg-zinc-100 dark:bg-zinc-800';
  }
  switch (value) {
    case 'positive':
      return 'bg-green-100 dark:bg-green-900/40';
    case 'negative':
      return 'bg-red-100 dark:bg-red-900/40';
    case 'neutral':
      return 'bg-zinc-200 dark:bg-zinc-700';
    default:
      return 'bg-zinc-100 dark:bg-zinc-800';
  }
}

/**
 * Get border color class for a vote value
 */
export function getVoteBorderColorClass(value: VoteValue, isSelected = false): string {
  if (!isSelected) {
    return 'border-zinc-200 dark:border-zinc-700';
  }
  switch (value) {
    case 'positive':
      return 'border-green-300 dark:border-green-700';
    case 'negative':
      return 'border-red-300 dark:border-red-700';
    case 'neutral':
      return 'border-zinc-400 dark:border-zinc-500';
    default:
      return 'border-zinc-200 dark:border-zinc-700';
  }
}

/**
 * Get localized label for a vote value (Už/Prieš/Susilaikė)
 * Short form for display in indicators
 */
export function getVoteDisplayLabel(value: VoteValue): string {
  switch (value) {
    case 'positive':
      return $t('Už');
    case 'negative':
      return $t('Prieš');
    case 'neutral':
      return $t('Susilaikė');
    default:
      return $t('—');
  }
}

/**
 * Get localized label for student benefit value
 */
export function getStudentBenefitLabel(value: VoteValue): string {
  switch (value) {
    case 'positive':
      return $t('Naudinga');
    case 'negative':
      return $t('Nenaudinga');
    case 'neutral':
      return $t('Neutrali');
    default:
      return $t('Nenustatyta');
  }
}

/**
 * Check if an agenda item has any decision data to display
 * Returns true if main_vote has student_vote, decision, or student_benefit set
 */
export function hasDecisionData(item: AgendaItem): boolean {
  const mainVote = getMainVote(item);
  if (!mainVote) return false;
  return mainVote.student_vote !== null || mainVote.decision !== null || mainVote.student_benefit !== null;
}

/**
 * Calculate student vote success rate for a list of agenda items
 * Returns percentage (0-100) of items where student_vote === decision
 * @param items - Array of agenda items with votes/main_vote loaded
 */
export function calculateSuccessRate(items: AgendaItem[]): number {
  if (items.length === 0) return 0;

  const itemsWithVotes = items.filter((item) => {
    const mainVote = getMainVote(item);
    return mainVote?.student_vote && mainVote?.decision;
  });

  if (itemsWithVotes.length === 0) return 0;

  const successfulItems = itemsWithVotes.filter((item) => {
    const mainVote = getMainVote(item);
    return mainVote?.student_vote === mainVote?.decision;
  });

  return Math.round((successfulItems.length / itemsWithVotes.length) * 100);
}

/**
 * Get success rate color class based on percentage
 */
export function getSuccessRateColorClass(rate: number): string {
  if (rate >= 75) return 'text-green-600 dark:text-green-400 font-medium';
  if (rate >= 50) return 'text-amber-600 dark:text-amber-400 font-medium';
  return 'text-red-600 dark:text-red-400 font-medium';
}

/**
 * Get vote alignment label for meeting-level display
 */
export function getVoteAlignmentLabel(status: MeetingStatusSummary['voteAlignmentStatus']): string {
  switch (status) {
    case 'all_match':
      return $t('Pozicija priimta');
    case 'all_mismatch':
      return $t('Pozicija nepriimta');
    case 'mixed':
      return $t('Dalinai priimta');
    case 'neutral':
      return $t('Neutralūs sprendimai');
    default:
      return $t('Nėra duomenų');
  }
}

/**
 * Get badge variant for vote alignment status
 */
export function getVoteAlignmentVariant(
  status: MeetingStatusSummary['voteAlignmentStatus'],
): 'default' | 'destructive' | 'secondary' {
  switch (status) {
    case 'all_match':
      return 'default';
    case 'all_mismatch':
      return 'destructive';
    default:
      return 'secondary';
  }
}

// ============================================================================
// Individual Agenda Item Vote Comparison Helpers
// ============================================================================

/**
 * Check if an agenda item has both student vote and decision for comparison
 */
export function canCompareVotes(item: AgendaItem): boolean {
  const mainVote = getMainVote(item);
  return !!(mainVote?.student_vote && mainVote?.decision);
}

/**
 * Check if student vote matches the decision for an agenda item
 */
export function isVoteAligned(item: AgendaItem): boolean {
  const mainVote = getMainVote(item);
  if (!mainVote?.student_vote || !mainVote?.decision) return false;
  return mainVote.student_vote === mainVote.decision;
}

/**
 * Get vote comparison text for an agenda item
 */
export function getVoteComparisonText(item: AgendaItem): string {
  if (!canCompareVotes(item)) return '';
  return isVoteAligned(item)
    ? $t('Studentų pozicija priimta')
    : $t('Studentų pozicija nesutampa su sprendimu');
}

/**
 * Get vote comparison color class for an agenda item
 */
export function getVoteComparisonColorClass(item: AgendaItem): string {
  if (!canCompareVotes(item)) return 'text-zinc-400 dark:text-zinc-500';
  return isVoteAligned(item)
    ? 'text-green-600 dark:text-green-500'
    : 'text-amber-600 dark:text-amber-500';
}

/**
 * Composable hook for agenda item styling
 */
export function useAgendaItemStyling() {
  return {
    // Agenda item functions
    getMainVote,
    getAgendaItemStatus,
    getAgendaItemStatusMeta,
    getMeetingStatusSummary,
    getNumberBadgeClass,
    getStatusText,
    getStatusIcon,
    getStatusTextClass,
    hasDecisionData,
    // Vote label functions
    getStudentVoteLabel,
    getStudentVoteShort,
    getDecisionLabel,
    getVoteDisplayLabel,
    getStudentBenefitLabel,
    // Vote styling functions
    getVoteBadgeClass,
    getStudentVoteAlignmentClass,
    getVoteStatusDotClass,
    getVoteTextColorClass,
    getVoteBgColorClass,
    getVoteBorderColorClass,
    getStudentBenefitBgClass,
    getDecisionTextColorClass,
    getStudentVoteIconClass,
    // Meeting-level functions
    calculateSuccessRate,
    getSuccessRateColorClass,
    getVoteAlignmentLabel,
    getVoteAlignmentVariant,
    // Agenda item vote comparison
    canCompareVotes,
    isVoteAligned,
    getVoteComparisonText,
    getVoteComparisonColorClass,
  };
}
