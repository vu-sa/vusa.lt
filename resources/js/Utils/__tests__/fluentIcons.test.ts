import { describe, expect, it } from 'vitest';

import { normalizeFluentIcons } from '../../../../vite-plugins/fluent-icons';

describe('Fluent icon catalogue', () => {
  it('expands aliases and collection dimensions for first-render SVGs', () => {
    const icons = normalizeFluentIcons({
      prefix: 'fluent', width: 24, height: 24,
      icons: { star: { body: '<path />' }, small: { body: '<circle />', width: 16, height: 16 } },
      aliases: { flipped: { parent: 'star', hFlip: true }, missing: { parent: 'unknown' } },
    });

    expect(icons.star).toMatchObject({ body: '<path />', width: 24, height: 24 });
    expect(icons.small).toMatchObject({ width: 16, height: 16 });
    expect(icons.flipped).toMatchObject({ body: '<path />', width: 24, height: 24, hFlip: true });
    expect(icons.missing).toBeUndefined();
  });
});
