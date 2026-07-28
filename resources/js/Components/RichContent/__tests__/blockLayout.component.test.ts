import { describe, expect, it } from 'vitest';

import { blockLayoutClasses } from '../blockLayout';

describe('blockLayoutClasses', () => {
  it('maps the registry default width to the matching rc-canvas column class', () => {
    // hero defaults to `full` and is selfSpaced, so both classes should be present.
    expect(blockLayoutClasses({ type: 'hero' })).toEqual(['rc-full', 'rc-flush']);
  });

  it('emits no width class for the default prose column', () => {
    expect(blockLayoutClasses({ type: 'tiptap' })).toEqual([]);
  });

  it('lets options.width override the registry default', () => {
    expect(blockLayoutClasses({ type: 'hero', options: { width: 'content' } })).toEqual(['rc-content', 'rc-flush']);
  });

  it('falls back to tiptap for an unknown type', () => {
    expect(blockLayoutClasses({ type: 'does-not-exist' })).toEqual([]);
  });

  it('omits rc-flush for types that are not selfSpaced', () => {
    expect(blockLayoutClasses({ type: 'image-grid' })).toEqual(['rc-wide']);
  });
});
