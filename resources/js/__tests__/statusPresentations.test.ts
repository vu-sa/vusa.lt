import { describe, expect, it } from 'vitest';

import {
  contentStatuses,
  institutionActivityStatuses,
  reservationResourceStatuses,
  studentBenefitStatuses,
  supportRequestStatuses,
  taskStatuses,
  voteStatuses,
} from '@/Constants/statuses';
import { InstitutionActivityStatus, SupportRequestStatus, VoteValue } from '@/Types/enums';

describe('status presentation maps', () => {
  it('covers every reservation resource state', () => {
    expect(Object.keys(reservationResourceStatuses)).toEqual([
      'created',
      'reserved',
      'lent',
      'returned',
      'rejected',
      'cancelled',
    ]);
  });

  it('covers votes and student benefit including the missing state', () => {
    expect(Object.keys(voteStatuses)).toEqual([...Object.values(VoteValue), 'not_recorded']);
    expect(Object.keys(studentBenefitStatuses)).toEqual([...Object.values(VoteValue), 'unknown']);
  });

  it('covers the task and content state contracts', () => {
    expect(Object.keys(taskStatuses)).toEqual(['completed', 'open', 'due_soon', 'overdue']);
    expect(Object.keys(contentStatuses)).toEqual(['published', 'scheduled', 'draft']);
  });

  it('covers every support request enum value', () => {
    expect(Object.keys(supportRequestStatuses)).toEqual(Object.values(SupportRequestStatus));
  });

  it('covers every institution activity status enum value', () => {
    expect(Object.keys(institutionActivityStatuses)).toEqual(Object.values(InstitutionActivityStatus));
  });

  it('keeps success and danger distinguishable without colour', () => {
    expect(taskStatuses.completed.icon).not.toBe(taskStatuses.overdue.icon);
    expect(voteStatuses.positive.icon).not.toBe(voteStatuses.negative.icon);
    expect(studentBenefitStatuses.positive.icon).not.toBe(studentBenefitStatuses.negative.icon);
    expect(institutionActivityStatuses.healthy.icon).not.toBe(institutionActivityStatuses.overdue.icon);
  });
});
