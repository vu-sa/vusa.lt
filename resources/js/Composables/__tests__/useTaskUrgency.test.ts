import { describe, expect, it } from 'vitest';

import { countIncompleteTasks, isTaskIncomplete } from '@/Composables/useTaskUrgency';

const task = (completedAt: string | null | undefined) => ({ completed_at: completedAt });

describe('isTaskIncomplete', () => {
  it('treats a null completed_at as outstanding', () => {
    expect(isTaskIncomplete(task(null))).toBe(true);
  });

  it('treats a missing completed_at as outstanding', () => {
    // Some payloads omit the field entirely rather than sending null.
    expect(isTaskIncomplete(task(undefined))).toBe(true);
  });

  it('treats a completed task as done', () => {
    expect(isTaskIncomplete(task('2026-03-04T10:00:00.000Z'))).toBe(false);
  });
});

describe('countIncompleteTasks', () => {
  it('counts only the tasks still needing action', () => {
    expect(countIncompleteTasks([
      task(null),
      task('2026-03-04T10:00:00.000Z'),
      task(undefined),
      task('2026-03-05T10:00:00.000Z'),
    ])).toBe(2);
  });

  it('returns zero when everything is done', () => {
    // The tab badge then renders nothing at all, rather than a stale total.
    expect(countIncompleteTasks([task('2026-03-04T10:00:00.000Z')])).toBe(0);
  });

  it('tolerates a missing list', () => {
    expect(countIncompleteTasks()).toBe(0);
    expect(countIncompleteTasks([])).toBe(0);
  });
});
