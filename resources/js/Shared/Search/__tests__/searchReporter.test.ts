import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { trackEvent } from '@/Plugins/umami';
import { createSearchReporter } from '@/Shared/Search/searchReporter';

vi.mock('@/Plugins/umami', () => ({ trackEvent: vi.fn() }));

describe('createSearchReporter', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    vi.mocked(trackEvent).mockClear();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('reports only the settled term, tagged with its source', async () => {
    const reporter = createSearchReporter('documents');

    reporter.report('sti', 4);
    await vi.advanceTimersByTimeAsync(500);
    reporter.report('Stipendija ', 2);

    expect(trackEvent).not.toHaveBeenCalled();

    await vi.advanceTimersByTimeAsync(2000);

    expect(trackEvent).toHaveBeenCalledTimes(1);
    expect(trackEvent).toHaveBeenCalledWith('search_submitted', {
      term: 'stipendija',
      results: 2,
      source: 'documents',
    });
  });

  it('does not re-report the same term until reset', async () => {
    const reporter = createSearchReporter('documents');

    reporter.report('nuostatai', 3);
    await vi.advanceTimersByTimeAsync(2000);
    reporter.report('nuostatai', 1);
    await vi.advanceTimersByTimeAsync(2000);

    expect(trackEvent).toHaveBeenCalledTimes(1);

    reporter.reset();
    reporter.report('nuostatai', 1);
    await vi.advanceTimersByTimeAsync(2000);

    expect(trackEvent).toHaveBeenCalledTimes(2);
  });

  it('caps the reported term at 60 characters', async () => {
    const reporter = createSearchReporter('global');

    reporter.report('a'.repeat(80), 0);
    await vi.advanceTimersByTimeAsync(2000);

    const [, payload] = vi.mocked(trackEvent).mock.calls[0];
    expect(payload!.term).toBe('a'.repeat(60));
  });
});
