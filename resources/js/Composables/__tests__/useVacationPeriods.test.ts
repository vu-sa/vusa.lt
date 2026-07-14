import { describe, test, expect, vi, beforeEach } from 'vitest';

const mockFetch = vi.fn();
vi.stubGlobal('fetch', mockFetch);

function respondWith(periods: Array<{ start: string; end: string; type: string }>) {
  mockFetch.mockResolvedValueOnce({
    ok: true,
    json: async () => ({ success: true, data: periods }),
  });
}

const summer2025 = { start: '2025-07-01', end: '2025-08-31', type: 'summer' };
const winter2025 = { start: '2025-12-24', end: '2026-01-01', type: 'winter' };

/**
 * The composable caches periods in module state, so each test imports a fresh copy.
 */
async function freshComposable() {
  vi.resetModules();
  const { useVacationPeriods } = await import('../useVacationPeriods');
  return useVacationPeriods();
}

describe('useVacationPeriods', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  test('fetches the requested years and parses periods into dates', async () => {
    respondWith([summer2025, winter2025]);

    const { periods, ensureYears } = await freshComposable();
    await ensureYears(2025, 2025);

    expect(mockFetch).toHaveBeenCalledTimes(1);
    expect(mockFetch.mock.calls[0][0]).toContain('from_year=2025');
    expect(periods.value).toHaveLength(2);

    const summer = periods.value.find(period => period.type === 'summer');

    expect(summer?.start).toBeInstanceOf(Date);
    expect(summer?.start.getFullYear()).toBe(2025);
    expect(summer?.start.getMonth()).toBe(6); // July
    expect(summer?.end.getDate()).toBe(31);
  });

  test('does not refetch a year that is already loaded', async () => {
    respondWith([summer2025]);

    const { ensureYears } = await freshComposable();
    await ensureYears(2025, 2025);
    await ensureYears(2025, 2025);

    expect(mockFetch).toHaveBeenCalledTimes(1);
  });

  test('only fetches years that are missing', async () => {
    respondWith([summer2025]);
    respondWith([{ start: '2026-07-01', end: '2026-08-31', type: 'summer' }]);

    const { periods, ensureYears } = await freshComposable();
    await ensureYears(2025, 2025);
    await ensureYears(2025, 2026);

    expect(mockFetch).toHaveBeenCalledTimes(2);
    expect(mockFetch.mock.calls[1][0]).toContain('from_year=2026');
    expect(periods.value).toHaveLength(2);
  });

  test('shares one request between concurrent callers', async () => {
    respondWith([summer2025]);

    const { ensureYears } = await freshComposable();
    await Promise.all([ensureYears(2025, 2025), ensureYears(2025, 2025)]);

    expect(mockFetch).toHaveBeenCalledTimes(1);
  });

  test('fails silently and allows a retry', async () => {
    mockFetch.mockResolvedValueOnce({ ok: false, status: 500 });

    const { periods, ensureYears } = await freshComposable();
    await ensureYears(2025, 2025);

    expect(periods.value).toHaveLength(0);

    respondWith([summer2025]);
    await ensureYears(2025, 2025);

    expect(periods.value).toHaveLength(1);
  });

  test('periodsInRange returns only overlapping periods', async () => {
    respondWith([summer2025, winter2025]);

    const { ensureYears, periodsInRange } = await freshComposable();
    await ensureYears(2025, 2025);

    const overlapping = periodsInRange(new Date('2025-08-15'), new Date('2025-09-15'));

    expect(overlapping).toHaveLength(1);
    expect(overlapping[0].type).toBe('summer');
    expect(periodsInRange(new Date('2025-09-15'), new Date('2025-10-15'))).toHaveLength(0);
  });
});
