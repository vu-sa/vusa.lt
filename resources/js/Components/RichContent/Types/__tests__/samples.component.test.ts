import { describe, expect, it } from 'vitest';

import { getContentSample } from '../samples';
import { getAllContentTypes } from '../index';

const TYPES_WITHOUT_LIVE_PREVIEW = ['news', 'calendar', 'spotify-embed', 'social-embed'];

describe('getContentSample', () => {
  it('returns a sample for every previewable type', () => {
    for (const type of getAllContentTypes()) {
      if (TYPES_WITHOUT_LIVE_PREVIEW.includes(type.value)) continue;

      const sample = getContentSample(type.value);
      expect(sample, `expected a sample for "${type.value}"`).not.toBeNull();
      expect(sample!.json_content).toBeDefined();
    }
  });

  it('returns null for types that fetch live data or embed third-party content', () => {
    for (const type of TYPES_WITHOUT_LIVE_PREVIEW) {
      expect(getContentSample(type)).toBeNull();
    }
  });

  it('returns null for an unregistered type', () => {
    expect(getContentSample('does-not-exist')).toBeNull();
  });

  it('produces fresh objects on each call (no shared mutable state between callers)', () => {
    const first = getContentSample('shadcn-card');
    const second = getContentSample('shadcn-card');
    expect(first).not.toBe(second);
    expect(first!.json_content).not.toBe(second!.json_content);
  });
});
