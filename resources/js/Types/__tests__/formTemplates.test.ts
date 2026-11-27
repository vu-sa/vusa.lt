import { afterEach, expect, it, vi } from 'vitest';

afterEach(() => {
  vi.useRealTimers();
  vi.resetModules();
});

it('defaults the publish time to a timezone-qualified ISO instant', async () => {
  vi.useFakeTimers({ now: new Date('2026-09-19T22:30:00Z'), toFake: ['Date'] });
  vi.resetModules();

  const { newsTemplate } = await import('../formTemplates');

  expect(newsTemplate.publish_time).toBe('2026-09-19T22:30:00.000Z');
});
