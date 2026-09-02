import { describe, expect, it } from 'vitest';

import {
  getAgendaItemStatus,
  getAgendaItemStatusMeta,
  getMeetingStatusSummary,
} from '@/Composables/useAgendaItemStyling';

const vote = (overrides: Record<string, unknown> = {}) => ({
  id: 'v1',
  is_main: true,
  is_consensus: false,
  decision: null,
  student_vote: null,
  student_benefit: null,
  ...overrides,
});

const votingItem = (voteOverrides: Record<string, unknown> = {}) => ({
  id: 'item-1',
  type: 'voting',
  votes: [vote(voteOverrides)],
});

describe('getAgendaItemStatus - governance scope', () => {
  it('still demands the student perspective by default (external bodies)', () => {
    expect(getAgendaItemStatus(votingItem({ decision: 'positive' }))).toBe('no_vote');
    expect(getAgendaItemStatus(votingItem({ decision: 'positive' }), true)).toBe('no_vote');
  });

  it('treats a decision-only vote as decided for internal bodies', () => {
    expect(getAgendaItemStatus(votingItem({ decision: 'positive' }), false)).toBe('decision_positive');
    expect(getAgendaItemStatus(votingItem({ decision: 'negative' }), false)).toBe('decision_negative');
    expect(getAgendaItemStatus(votingItem({ decision: 'neutral' }), false)).toBe('neutral_decided');
  });

  it('keeps consensus, no-vote and non-voting statuses identical in both scopes', () => {
    expect(getAgendaItemStatus(votingItem({ decision: 'positive', is_consensus: true }), false)).toBe('consensus');
    expect(getAgendaItemStatus(votingItem(), false)).toBe('no_vote');
    expect(getAgendaItemStatus({ id: 'a', type: 'informational' }, false)).toBe('informational');
  });

  it('resolves external decisions with a student vote through the alignment statuses', () => {
    expect(getAgendaItemStatus(votingItem({ decision: 'positive', student_vote: 'positive' }))).toBe('student_aligned');
    expect(getAgendaItemStatus(votingItem({ decision: 'positive', student_vote: 'negative' }))).toBe('student_misaligned');
  });
});

describe('getAgendaItemStatusMeta - governance scope', () => {
  it('labels an internal body decision as decided instead of "Neaptartas"', () => {
    const meta = getAgendaItemStatusMeta(votingItem({ decision: 'positive' }), false);
    expect(meta.status).toBe('decision_positive');
    expect(meta.label).toBe('Priimtas');
  });

  it('keeps "Neaptartas" for an external body decision without a student vote', () => {
    expect(getAgendaItemStatusMeta(votingItem({ decision: 'positive' })).label).toBe('Neaptartas');
  });
});

describe('getMeetingStatusSummary - governance scope', () => {
  it('counts internal decisions as discussed voting items', () => {
    const items = [
      votingItem({ decision: 'positive' }),
      votingItem({ decision: 'negative' }),
      { id: 'item-3', type: 'informational' },
    ];

    const summary = getMeetingStatusSummary(items, false);

    expect(summary.decisionPositive).toBe(1);
    expect(summary.decisionNegative).toBe(1);
    expect(summary.noVote).toBe(0);
    expect(summary.overallStatus).toBe('complete');
    expect(summary.completionRate).toBe(100);
    // Alignment is undefined without a student position — mirrors the backend.
    expect(summary.voteAlignmentStatus).toBe('neutral');
  });

  it('keeps an internal body missing its decision incomplete', () => {
    const summary = getMeetingStatusSummary([votingItem(), votingItem({ decision: 'positive' })], false);

    expect(summary.noVote).toBe(1);
    expect(summary.overallStatus).toBe('incomplete');
    expect(summary.completionRate).toBe(50);
  });

  it('leaves the external-scope reading untouched', () => {
    const summary = getMeetingStatusSummary([votingItem({ decision: 'positive' })]);

    expect(summary.noVote).toBe(1);
    expect(summary.overallStatus).toBe('incomplete');
    expect(summary.decisionPositive).toBe(0);
    expect(summary.voteAlignmentStatus).toBe('unknown');
  });
});

describe('break agenda items', () => {
  const breakItem = () => ({ id: 'b1', type: 'break' as const, votes: [] });

  it('has its own status rather than falling through to no_vote', () => {
    // A pause records no outcome; treating it as a voting item left it flagged amber.
    expect(getAgendaItemStatus(breakItem() as never)).toBe('break');
  });

  it('is labelled as a break', () => {
    expect(getAgendaItemStatusMeta(breakItem() as never).label).toBe('Pertrauka');
  });

  it('is counted separately in a meeting summary', () => {
    const summary = getMeetingStatusSummary([breakItem()] as never);

    expect(summary.break).toBe(1);
    expect(summary.unset).toBe(0);
    expect(summary.noVote).toBe(0);
  });

  it('does not drag the overall meeting status to incomplete', () => {
    expect(getMeetingStatusSummary([breakItem()] as never).overallStatus).not.toBe('incomplete');
  });
});
