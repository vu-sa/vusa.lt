import { afterEach, expect, it, vi } from 'vitest';

import { serialRenderer } from '../serialRenderer';

afterEach(() => vi.useRealTimers());

it('retains the slot across a timeout and discards expired requests before switching locales', async () => {
  vi.useFakeTimers();
  let release!: (value: string) => void;
  const render = vi.fn((locale: string) => locale === 'lt'
    ? new Promise<string>((resolve) => { release = resolve; })
    : Promise.resolve(locale));
  const run = serialRenderer(render, 100, vi.fn());
  const first = expect(run('lt')).rejects.toThrow('deadline');
  const queued = expect(run('en')).rejects.toThrow('deadline');
  await vi.advanceTimersByTimeAsync(100);
  await Promise.all([first, queued]);
  await expect(run('en')).rejects.toThrow('deadline');
  expect(render).toHaveBeenCalledTimes(1);
  release('lt');
  await vi.advanceTimersByTimeAsync(1);
  await expect(run('en')).resolves.toBe('en');
});

it('continues after a render error and restarts a stuck renderer only after the grace period', async () => {
  vi.useFakeTimers();
  const restart = vi.fn();
  const run = serialRenderer(async (fail: boolean) => {
    if (fail) throw new Error('render failed');
    return new Promise<void>(() => {});
  }, 100, restart);
  const failed = expect(run(true)).rejects.toThrow('render failed');
  const stuck = expect(run(false)).rejects.toThrow('deadline');
  await vi.advanceTimersByTimeAsync(499);
  await Promise.all([failed, stuck]);
  expect(restart).not.toHaveBeenCalled();
  await vi.advanceTimersByTimeAsync(1);
  expect(restart).toHaveBeenCalledWith(500);
});
