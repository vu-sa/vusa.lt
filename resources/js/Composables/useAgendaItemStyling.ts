import { trans as $t } from 'laravel-vue-i18n';
import type { LucideIcon } from '@lucide/vue';

import { agendaItemStatuses, statusRoleParts, type AgendaItemStatus, type StatusRole } from '@/Constants/statuses';

export type { AgendaItemStatus };

// ============================================================================
// Type Definitions (exported for component usage)
// ============================================================================

export type VoteValue = 'positive' | 'negative' | 'neutral' | string | null | undefined;
export type AgendaItemType = 'voting' | 'informational' | 'deferred' | 'break' | 'withdrawn' | string | null | undefined;

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
 * Calculate the status of an agenda item from its type and outcome votes: the shared status of
 * those votes, or 'mixed' when they disagree.
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

  if (item.type === 'withdrawn') {
    return 'withdrawn';
  }

  const outcomeVotes = getOutcomeVotes(item);
  if (outcomeVotes.length === 0) {
    return 'no_vote';
  }

  const statuses = new Set(outcomeVotes.map(vote => getVoteStatus(vote, requiresStudentPerspective)));
  if (statuses.has('no_vote')) {
    return 'no_vote';
  }

  return statuses.size === 1 ? [...statuses][0]! : 'mixed';
}

/** The outcome a single vote records; an item's status combines these (see getAgendaItemStatus). */
export function getVoteStatus(vote: Vote, requiresStudentPerspective = true): AgendaItemStatus {
  if (!vote.decision) {
    return 'no_vote';
  }

  // Consensus takes priority over the other statuses
  if (vote.is_consensus) {
    return 'consensus';
  }

  // An internal body's vote has only an outcome: the decision is the whole story
  if (!requiresStudentPerspective) {
    if (vote.decision === 'positive') {
      return 'decision_positive';
    }
    if (vote.decision === 'negative') {
      return 'decision_negative';
    }
    return 'neutral_decided';
  }

  if (vote.decision === 'neutral') {
    return 'neutral_decided';
  }

  if (vote.student_vote) {
    return vote.student_vote === vote.decision ? 'student_aligned' : 'student_misaligned';
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
  mixed: number;
  noVote: number;
  deferred: number;
  informational: number;
  break: number;
  withdrawn: number;
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
    mixed: 0,
    noVote: 0,
    deferred: 0,
    informational: 0,
    break: 0,
    withdrawn: 0,
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
      case 'mixed':
        summary.mixed++;
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
      case 'withdrawn':
        summary.withdrawn++;
        break;
      case 'unset':
        summary.unset++;
        break;
    }
  }

  // Calculate completion rate (for voting items only)
  const votingItems = summary.consensus + summary.aligned + summary.misaligned
    + summary.decisionPositive + summary.decisionNegative + summary.neutralDecided + summary.mixed + summary.noVote;
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
 * The votes that decide the item's outcome: the main vote when one is marked, otherwise all of
 * them. Mirrors AgendaItem::outcomeVotes().
 */
export function getOutcomeVotes(item: AgendaItem): Vote[] {
  const mainVote = getMainVote(item);

  return mainVote ? [mainVote] : (item.votes ?? []);
}

/** The one vote whose fields stand for the item, or undefined when several votes share the outcome. */
export function getOutcomeVote(item: AgendaItem): Vote | undefined {
  const outcomeVotes = getOutcomeVotes(item);

  return outcomeVotes.length === 1 ? outcomeVotes[0] : undefined;
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
 * Get student vote label
 */
export function getStudentVoteLabel(studentVote: VoteValue): string {
  switch (studentVote) {
    case 'positive': return $t('Pritarė');
    case 'negative': return $t('Nepritarė');
    case 'neutral': return $t('Susilaikė');
    default: return '';
  }
}

/**
 * Get vote decision label
 */
export function getDecisionLabel(decision: VoteValue): string {
  switch (decision) {
    case 'positive': return $t('Priimtas');
    case 'negative': return $t('Atmestas');
    case 'neutral': return $t('Susilaikyta');
    default: return $t('Neaptartas');
  }
}

const roleText = (value: VoteValue, fallback = MUTED_TEXT): string => {
  const role = getVoteValueRole(value);
  return role ? statusRoleParts[role].text : fallback;
};

/** Decision ink that stays readable on any benefit surface. */
export function getDecisionTextColorClass(decision: VoteValue): string {
  return roleText(decision, 'text-foreground');
}

// ============================================================================
// Shared Vote Display Utilities (for both admin and public components)
// ============================================================================

/** Ink for a vote value, e.g. in the public VoteStatusIndicator. */
export function getVoteTextColorClass(value: VoteValue): string {
  return roleText(value, 'text-muted-foreground/60');
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
      return $t('Palanku');
    case 'negative':
      return $t('Nepalanku');
    case 'neutral':
      return $t('Neutralu');
    default:
      return $t('Nenustatyta');
  }
}

/** Whether any outcome vote has a student vote, decision or student benefit to display. */
export function hasDecisionData(item: AgendaItem): boolean {
  return getOutcomeVotes(item).some(vote => vote.student_vote != null || vote.decision != null || vote.student_benefit != null);
}

// ============================================================================
// Individual Agenda Item Vote Comparison Helpers
// ============================================================================

/**
 * Check if an agenda item has both student vote and decision for comparison
 */
export function canCompareVotes(item: AgendaItem): boolean {
  const mainVote = getOutcomeVote(item);
  return !!(mainVote?.student_vote && mainVote?.decision);
}

/**
 * Check if student vote matches the decision for an agenda item
 */
export function isVoteAligned(item: AgendaItem): boolean {
  const mainVote = getOutcomeVote(item);
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
