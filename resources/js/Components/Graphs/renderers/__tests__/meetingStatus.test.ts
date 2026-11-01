import { describe, expect, it } from 'vitest';

import { ganttMeetingStatus } from '../meetingStatus';

describe('ganttMeetingStatus', () => {
  it('reports how the vote went once the record is complete', () => {
    expect(ganttMeetingStatus({ completion_status: 'complete', vote_alignment_status: 'all_mismatch' })?.label).toBe('Visi nesutampa');
  });

  it('puts an unfinished record ahead of the vote', () => {
    expect(ganttMeetingStatus({ completion_status: 'incomplete', vote_alignment_status: 'all_match' })?.label).toBe('Neužpildyta');
  });

  it('says only that a meeting cannot be read when the viewer has no access', () => {
    expect(ganttMeetingStatus({ authorized: false, completion_status: 'complete', vote_alignment_status: 'all_match' })?.label).toBe('Nėra prieigos');
  });
});
