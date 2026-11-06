import { trans as $t } from 'laravel-vue-i18n';

import { agendaItemStatuses, statusRoleParts, type AgendaItemStatus, type StatusRole } from '@/Constants/statuses';

export type { AgendaItemStatus };

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

export interface AgendaItemStatusMeta {
  status: AgendaItemStatus;
  role: StatusRole;
  label: string;
  colorClass: string;
  bgClass: string;
  borderClass: string;
  dotClass: string;
}

/** Mirrors the backend outcome votes; internal bodies have no student position to compare. */
export function getAgendaItemStatus(item: AgendaItem, requiresStudentPerspective = true): AgendaItemStatus {
  if (item.type === null || item.type === undefined) {
    return 'unset';
  }

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

  if (vote.is_consensus) {
    return 'consensus';
  }

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

  return 'no_vote';
}

export function getAgendaItemStatusMeta(item: AgendaItem, requiresStudentPerspective = true): AgendaItemStatusMeta {
  const status = getAgendaItemStatus(item, requiresStudentPerspective);
  const presentation = agendaItemStatuses[status];
  const parts = statusRoleParts[presentation.role];

  return {
    status,
    role: presentation.role,
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

export function getMainVote(item: AgendaItem): Vote | undefined {
  if (item.main_vote) {
    return item.main_vote;
  }
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

export function getNumberBadgeClass(item: AgendaItem, requiresStudentPerspective = true): string {
  const { role } = agendaItemStatuses[getAgendaItemStatus(item, requiresStudentPerspective)];
  const parts = statusRoleParts[role];

  return `${parts.surface} ${parts.text}`;
}

export function getStudentVoteLabel(studentVote: VoteValue): string {
  switch (studentVote) {
    case 'positive': return $t('Pritarė');
    case 'negative': return $t('Nepritarė');
    case 'neutral': return $t('Susilaikė');
    default: return '';
  }
}

export function getDecisionLabel(decision: VoteValue): string {
  switch (decision) {
    case 'positive': return $t('Priimtas');
    case 'negative': return $t('Nepriimtas');
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

/** Ink for a vote value, e.g. in the public VoteStatusIndicator. */
export function getVoteTextColorClass(value: VoteValue): string {
  return roleText(value, 'text-muted-foreground/60');
}

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

/** Votes stay on an item whose type later changes, so a deferred or withdrawn one must not show them. */
export function showsVoteOutcome(item: AgendaItem): boolean {
  return item.type === 'voting' && hasDecisionData(item);
}
