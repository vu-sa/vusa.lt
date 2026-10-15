import { describe, expect, it } from 'vitest';

import { timelineDutyName, timelineSourceDutyName } from '../dutyNames';
import type { TimelineRow } from '../types';

const row = {
  holder_name: 'Petras', holder_pronouns: { lt: 'ji/jos', en: 'she/her' }, duty_name: 'Koordinatorius',
  use_original_duty_name: false, source: { id: 'source', duty_name: 'Kuratorius', use_original_duty_name: true },
} as TimelineRow;

describe('timeline assignment names', () => {
  it('uses pronouns for the assignment and the source’s own override for its label', () => {
    expect(timelineDutyName(row, 'lt')).toBe('Koordinatorė');
    expect(timelineSourceDutyName(row, 'lt')).toBe('Kuratorius');
  });

  it('preserves English and supports payloads without pronouns', () => {
    expect(timelineDutyName({ ...row, duty_name: 'Coordinator' }, 'en')).toBe('Coordinator');
    expect(timelineDutyName({ ...row, holder_name: 'Petras', holder_pronouns: undefined, duty_name: 'Koordinatorė' }, 'lt')).toBe('Koordinatorius');
  });
});
