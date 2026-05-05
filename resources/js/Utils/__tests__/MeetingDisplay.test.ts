import { describe, it, expect } from 'vitest';

import { formatMeetingDateTime, formatMeetingTimeOnly, isEmailMeeting } from '../MeetingDisplay';

const ISO = '2026-05-06T23:59:59';

describe('isEmailMeeting', () => {
  it('returns true when type is the "email" string', () => {
    expect(isEmailMeeting({ type: 'email' })).toBe(true);
  });

  it('returns true when type is an enum-like object with value "email"', () => {
    expect(isEmailMeeting({ type: { value: 'email' } })).toBe(true);
  });

  it('returns false for in-person and remote meetings', () => {
    expect(isEmailMeeting({ type: 'in-person' })).toBe(false);
    expect(isEmailMeeting({ type: 'remote' })).toBe(false);
  });

  it('returns false for nullish or missing type', () => {
    expect(isEmailMeeting({ type: null })).toBe(false);
    expect(isEmailMeeting({ type: undefined })).toBe(false);
    expect(isEmailMeeting({})).toBe(false);
  });
});

describe('formatMeetingDateTime', () => {
  it('strips hour/minute keys for email meetings', () => {
    const out = formatMeetingDateTime({ start_time: ISO, type: 'email' });
    expect(out).not.toMatch(/23:59|23\.59/);
    expect(out).toMatch(/2026/);
  });

  it('keeps hour/minute for in-person meetings', () => {
    const out = formatMeetingDateTime({ start_time: ISO, type: 'in-person' });
    expect(out).toMatch(/23:59|23\.59/);
  });

  it('respects custom options but still strips time for email', () => {
    const out = formatMeetingDateTime(
      { start_time: ISO, type: 'email' },
      { month: 'short', day: 'numeric', hour: 'numeric', minute: 'numeric' },
    );
    expect(out).not.toMatch(/23:59|23\.59/);
  });

  it('also strips timeStyle for email meetings', () => {
    const out = formatMeetingDateTime(
      { start_time: ISO, type: 'email' },
      { dateStyle: 'medium', timeStyle: 'short' },
    );
    expect(out).not.toMatch(/23:59|23\.59/);
  });
});

describe('formatMeetingTimeOnly', () => {
  it('returns null for email meetings', () => {
    expect(formatMeetingTimeOnly({ start_time: ISO, type: 'email' })).toBeNull();
  });

  it('returns a HH:MM string for non-email meetings', () => {
    const out = formatMeetingTimeOnly({ start_time: ISO, type: 'in-person' });
    expect(out).toMatch(/^\d{2}[:.]\d{2}$/);
  });
});
