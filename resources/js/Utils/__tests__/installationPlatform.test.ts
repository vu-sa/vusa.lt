import { describe, expect, it } from 'vitest';

import { installationPlatform } from '../installationPlatform';

describe('installationPlatform', () => {
  it.each([
    ['Mozilla iPhone', 'iPhone', 1, 'ios'],
    ['Mozilla iPad', 'iPad', 5, 'ios'],
    ['Mozilla Macintosh', 'MacIntel', 5, 'ios'],
    ['Mozilla Android', 'Linux', 5, 'android'],
    ['Mozilla Macintosh', 'MacIntel', 0, 'desktop'],
    ['Mozilla Windows', 'Win32', 0, 'desktop'],
  ])('suggests installation steps for %s on %s', (agent, platform, touchPoints, expected) => {
    expect(installationPlatform(agent, platform, touchPoints)).toBe(expected);
  });
});
