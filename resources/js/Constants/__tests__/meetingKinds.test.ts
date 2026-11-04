import { describe, expect, it } from 'vitest';

import { meetingKindFromTypes } from '../meetingKinds';

import { MeetingKind } from '@/Types/enums';

describe('meetingKindFromTypes', () => {
  const types = [
    { id: 7, extra_attributes: { meeting_kind: MeetingKind.Conference } },
    { id: 3, extra_attributes: { meeting_kind: MeetingKind.Assembly } },
    { id: 1, extra_attributes: null },
  ];

  it('takes the lowest-id selected type that declares a kind, like the server', () => {
    expect(meetingKindFromTypes([7, 3, 1], types)).toBe(MeetingKind.Assembly);
    expect(meetingKindFromTypes([7], types)).toBe(MeetingKind.Conference);
  });

  it('falls back to a posėdis when no selected type declares one', () => {
    expect(meetingKindFromTypes([1], types)).toBe(MeetingKind.Sitting);
    expect(meetingKindFromTypes([], types)).toBe(MeetingKind.Sitting);
  });
});
