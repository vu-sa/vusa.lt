import { trans as $t } from 'laravel-vue-i18n';
import type { LucideIcon } from 'lucide-vue-next';

import {
  agendaItemStatuses,
  statusRoleParts,
  type AgendaItemStatus,
  type StatusRole,
} from '@/Constants/statuses';

export type { AgendaItemStatus };

// ============================================================================
// Type Definitions (exported for component usage)
// ============================================================================

export type VoteValue = 'positive' | 'negative' | 'neutral' | string | null | undefined;
export type AgendaItemType = 'voting' | 'informational' | 'deferred' | string | null | undefined;

export interface Vote {
  id?: string | number;
  is_main?: boolean;
  is_consensus?: boolean;
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
 * Status metadata for consistent display across components
 */
export interface AgendaItemStatusMeta {
  status: AgendaItemStatus;
  role: StatusRole;
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
 * @param requiresStudentPerspective - False for VU SA's own bodies: there is no
 *   student position to compare against, so a recorded decision alone means decided.
 * @returns The calculated status
 */
export function getAgendaItemStatus(item: AgendaItem, requiresStudentPerspective = true): AgendaItemStatus {
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

  if (item.type === 'break') {
    return 'break';
  }

  // Voting type - check main vote
  const mainVote = getMainVote(item);

  // No vote recorded yet
  if (!mainVote?.decision) {
    return 'no_vote';
  }

  // Check if consensus vote (takes priority over other statuses)
  if (mainVote.is_consensus) {
    return 'consensus';
  }

  // An internal body's vote has only an outcome: the decision is the whole story
  if (!requiresStudentPerspective) {
    if (mainVote.decision === 'positive') {
      return 'decision_positive';
    }
    if (mainVote.decision === 'negative') {
      return 'decision_negative';
    }
    return 'neutral_decided';
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
export function getAgendaItemStatusMeta(item: AgendaItem, requiresStudentPerspective = true): AgendaItemStatusMeta {
  const status = getAgendaItemStatus(item, requiresStudentPerspective);
  const presentation = agendaItemStatuses[status];
  const parts = statusRoleParts[presentation.role];

  return {
    status,
    role: presentation.role,
    icon: presentation.icon,
    label: $t(presentation.label),
    colorClass: parts.text,
    bgClass: parts.surface,
    borderClass: parts.border,
    dotClass: parts.dot,
  };
}

/** Role of a single vote value: a recorded yes/no/abstain, or nothing recorded yet. */
export function getVoteValueRole(value: VoteValue): StatusRole | null {
  switch (value) {
    case 'positive': return 'success';
    case 'negative': return 'danger';
    case 'neutral': return 'neutral';
    default: return null;
  }
}

const MUTED_TEXT = 'text-muted-foreground';

/**
 * Calculate meeting-level status summary from agenda items
 * Used for Gantt chart and meeting list displays
 */
export interface MeetingStatusSummary {
  totalItems: number;
  consensus: number;
  aligned: number;
  misaligned: number;
  decisionPositive: number;
  decisionNegative: number;
  neutralDecided: number;
  noVote: number;
  deferred: number;
  informational: number;
  break: number;
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
 * @param requiresStudentPerspective - False for VU SA's own bodies — see getAgendaItemStatus()
 */
export function getMeetingStatusSummary(items: AgendaItem[], requiresStudentPerspective = true): MeetingStatusSummary {
  const summary: MeetingStatusSummary = {
    totalItems: items.length,
    consensus: 0,
    aligned: 0,
    misaligned: 0,
    decisionPositive: 0,
    decisionNegative: 0,
    neutralDecided: 0,
    noVote: 0,
    deferred: 0,
    informational: 0,
    break: 0,
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
    const status = getAgendaItemStatus(item, requiresStudentPerspective);
    switch (status) {
      case 'consensus':
        summary.consensus++;
        break;
      case 'student_aligned':
        summary.aligned++;
        break;
      case 'student_misaligned':
        summary.misaligned++;
        break;
      case 'decision_positive':
        summary.decisionPositive++;
        break;
      case 'decision_negative':
        summary.decisionNegative++;
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
      case 'break':
        summary.break++;
        break;
      case 'unset':
        summary.unset++;
        break;
    }
  }

  // Calculate completion rate (for voting items only)
  const votingItems = summary.consensus + summary.aligned + summary.misaligned
    + summary.decisionPositive + summary.decisionNegative + summary.neutralDecided + summary.noVote;
  const completedVotingItems = votingItems - summary.noVote;

  if (votingItems > 0) {
    summary.completionRate = Math.round((completedVotingItems / votingItems) * 100);
  }

  // Calculate alignment rate (for items with both student vote and decision)
  // Consensus votes are considered aligned (all parties agreed)
  const alignableItems = summary.consensus + summary.aligned + summary.misaligned;
  if (alignableItems > 0) {
    summary.alignmentRate = Math.round(((summary.consensus + summary.aligned) / alignableItems) * 100);
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
    // Alignment is undefined without a student position — decided items read as neutral,
    // mirroring VoteStatisticsCalculator::alignmentStatus() on the backend.
    if (summary.neutralDecided > 0 || summary.decisionPositive > 0 || summary.decisionNegative > 0) {
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
export function getNumberBadgeClass(item: AgendaItem, requiresStudentPerspective = true): string {
  const { role } = agendaItemStatuses[getAgendaItemStatus(item, requiresStudentPerspective)];
  const parts = statusRoleParts[role];

  return `${parts.surface} ${parts.text}`;
}

/**
 * Get status text for an agenda item
 */
export function getStatusText(item: AgendaItem): string {
  // If type is not set, item needs type selection
  if (item.type === null || item.type === undefined) return $t('Nepažymėtas');

  if (item.type === 'informational') return $t('Informacinis');
  if (item.type === 'deferred') return $t('Atidėtas');
  if (item.type === 'break') return $t('Pertrauka');

  const mainVote = getMainVote(item);

  if (!mainVote?.decision) return $t('Neaptartas');

  if (mainVote.is_consensus) return $t('Bendras sutarimas');
  if (mainVote.decision === 'positive') return $t('Priimtas');
  if (mainVote.decision === 'negative') return $t('Atmestas');
  return $t('Neutralus');
}

/**
 * Get status icon (checkmark, X, or empty)
 */
export function getStatusIcon(item: AgendaItem): string {
  if (item.type === 'informational' || item.type === 'deferred' || item.type === 'break') return '';

  const mainVote = getMainVote(item);

  if (!mainVote?.decision) return '';
  if (mainVote.decision === 'positive') return '✓';
  if (mainVote.decision === 'negative') return '✗';
  return '';
}

/**
 * Get status text class for coloring
 */
export function getStatusTextClass(item: AgendaItem, requiresStudentPerspective = true): string {
  const status = getAgendaItemStatus(item, requiresStudentPerspective);

  // An undiscussed vote is a quiet gap in a list, not an alarm on every row.
  return status === 'no_vote' ? MUTED_TEXT : statusRoleParts[agendaItemStatuses[status].role].text;
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

const roleText = (value: VoteValue, fallback = MUTED_TEXT): string => {
  const role = getVoteValueRole(value);
  return role ? statusRoleParts[role].text : fallback;
};

/**
 * Decision colours the badge; its border turns danger when the students' vote lost.
 */
export function getVoteBadgeClass(vote: Vote): string {
  const role = getVoteValueRole(vote.decision);

  if (!role || role === 'neutral') {
    return `${statusRoleParts.neutral.surface} ${statusRoleParts.neutral.text} ${statusRoleParts.neutral.border}`;
  }

  const aligned = vote.decision === vote.student_vote;
  const border = aligned ? statusRoleParts[role].border : statusRoleParts.danger.border;

  return `${statusRoleParts[role].surface} ${statusRoleParts[role].text} ${border}`;
}

/** Student vote ink: alignment when both are known, otherwise the vote itself. */
export function getStudentVoteAlignmentClass(vote: Vote): string {
  if (vote.decision && vote.student_vote) {
    return vote.decision === vote.student_vote ? statusRoleParts.success.text : statusRoleParts.danger.text;
  }

  return roleText(vote.student_vote);
}

/** Surface for a vote badge, from the perceived benefit to students. */
export function getStudentBenefitBgClass(benefit: VoteValue): string {
  return statusRoleParts[getVoteValueRole(benefit) ?? 'neutral'].surface;
}

/** Decision ink that stays readable on any benefit surface. */
export function getDecisionTextColorClass(decision: VoteValue): string {
  return roleText(decision, 'text-foreground');
}

export function getStudentVoteIconClass(studentVote: VoteValue): string {
  return roleText(studentVote);
}

export function getVoteStatusDotClass(vote: Vote): string {
  if (!vote.decision && !vote.student_vote) {
    return 'bg-muted-foreground/40';
  }

  return statusRoleParts[getVoteValueRole(vote.decision) ?? 'neutral'].dot;
}

// ============================================================================
// Shared Vote Display Utilities (for both admin and public components)
// ============================================================================

/** Ink for a vote value, e.g. in the public VoteStatusIndicator. */
export function getVoteTextColorClass(value: VoteValue): string {
  return roleText(value, 'text-muted-foreground/60');
}

export function getVoteBgColorClass(value: VoteValue, isSelected = false): string {
  if (!isSelected) {
    return 'bg-muted';
  }

  return statusRoleParts[getVoteValueRole(value) ?? 'neutral'].surface;
}

export function getVoteBorderColorClass(value: VoteValue, isSelected = false): string {
  if (!isSelected) {
    return 'border-border';
  }

  return statusRoleParts[getVoteValueRole(value) ?? 'neutral'].border;
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
  if (rate >= 75) return `${statusRoleParts.success.text} font-medium`;
  if (rate >= 50) return `${statusRoleParts.attention.text} font-medium`;
  return `${statusRoleParts.danger.text} font-medium`;
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
    : $t('Studentų pozicija nesutampa');
}

/**
 * Get vote comparison color class for an agenda item
 */
export function getVoteComparisonColorClass(item: AgendaItem): string {
  if (!canCompareVotes(item)) return MUTED_TEXT;
  return isVoteAligned(item) ? statusRoleParts.success.text : statusRoleParts.attention.text;
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
    getVoteValueRole,
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
