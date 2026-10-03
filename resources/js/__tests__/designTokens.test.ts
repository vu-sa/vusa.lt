import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

import { describe, expect, it } from 'vitest';

/**
 * Guards the colour-system token list (.ai/rules/css.md)
 * so a rename can't silently drop a role or category — the "snapshot test" the rules doc asks
 * for. Contrast itself is checked in Storybook (ColourSystem.stories.ts, a11y: 'error'), not
 * here: jsdom has no CSS cascade, so this only asserts the tokens exist and are wired the way
 * the rules require — var()-indirected in the Tailwind mapping, not inlined, so a surface could
 * re-scope them later.
 */
function readCssImportGraph(path: string): string {
  return readFileSync(path, 'utf-8').replace(
    /@import ['"](\.\/[^'"]+\.css)['"];/g,
    (_, importPath: string) => readCssImportGraph(resolve(dirname(path), importPath)),
  );
}

const appCssPath = resolve(dirname(fileURLToPath(import.meta.url)), '../../css/app.css');
const css = readCssImportGraph(appCssPath);

const ROLES = ['neutral', 'info', 'progress', 'attention', 'success', 'danger'] as const;
const CATEGORIES = Array.from({ length: 8 }, (_, i) => i + 1);

describe('status role tokens', () => {
  it.each(ROLES)('%s is defined in :root and .dark with text/surface/border', (role) => {
    const rootBlock = css.match(/:root\s*\{([\s\S]*?)\n\}/)?.[1] ?? '';
    const darkBlock = css.match(/\n\.dark\s*\{([\s\S]*?)\n\}/)?.[1] ?? '';

    for (const block of [rootBlock, darkBlock]) {
      expect(block).toMatch(new RegExp(`--status-${role}:\\s*oklch`));
      expect(block).toMatch(new RegExp(`--status-${role}-surface:\\s*oklch`));
    }
    // -border is a color-mix of its own role's text token, declared once (:root) — reused via
    // the cascade rather than restated per theme, see base-tokens.css's comment.
    expect(rootBlock).toMatch(new RegExp(`--status-${role}-border:\\s*color-mix\\(in oklab, var\\(--status-${role}\\)`));
  });

  it.each(ROLES)('%s is mapped through var(), not inlined, in the Tailwind theme', (role) => {
    expect(css).toMatch(new RegExp(`--color-status-${role}:\\s*var\\(--status-${role}\\);`));
    expect(css).toMatch(new RegExp(`--color-status-${role}-surface:\\s*var\\(--status-${role}-surface\\);`));
    expect(css).toMatch(new RegExp(`--color-status-${role}-border:\\s*var\\(--status-${role}-border\\);`));
  });
});

describe('categorical tokens', () => {
  it.each(CATEGORIES)('--cat-%i is defined in :root and .dark', (n) => {
    const rootBlock = css.match(/:root\s*\{([\s\S]*?)\n\}/)?.[1] ?? '';
    const darkBlock = css.match(/\n\.dark\s*\{([\s\S]*?)\n\}/)?.[1] ?? '';

    expect(rootBlock).toMatch(new RegExp(`--cat-${n}:\\s*oklch`));
    expect(darkBlock).toMatch(new RegExp(`--cat-${n}:\\s*oklch`));
    // -surface is color-mix'd against --background and declared once — it re-resolves under
    // .dark through the cascade (both --cat-n and --background are overridden there), so it is
    // deliberately absent from the .dark block itself.
    expect(rootBlock).toMatch(new RegExp(`--cat-${n}-surface:\\s*color-mix\\(in oklab, var\\(--cat-${n}\\)`));
  });

  it.each(CATEGORIES)('--cat-%i is mapped through var(), not inlined, in the Tailwind theme', (n) => {
    expect(css).toMatch(new RegExp(`--color-cat-${n}:\\s*var\\(--cat-${n}\\);`));
    expect(css).toMatch(new RegExp(`--color-cat-${n}-surface:\\s*var\\(--cat-${n}-surface\\);`));
  });

  it('no two categorical hues are closer than 25° apart', () => {
    const hues = CATEGORIES.map((n) => {
      const match = css.match(new RegExp(`--cat-${n}:\\s*oklch\\([\\d.]+\\s+[\\d.]+\\s+([\\d.]+)\\)`));
      if (!match) {
        throw new Error(`--cat-${n} not found`);
      }
      return Number(match[1]);
    });

    for (let i = 0; i < hues.length; i++) {
      for (let j = i + 1; j < hues.length; j++) {
        const diff = Math.abs(hues[i] - hues[j]);
        const circularDiff = Math.min(diff, 360 - diff);
        expect(circularDiff).toBeGreaterThanOrEqual(25);
      }
    }
  });
});

describe('legacy status token removal', () => {
  it('the old -muted/-foreground suffixes are gone (zero consumers before this PR)', () => {
    expect(css).not.toMatch(/--color-status-\w+-muted:/);
    expect(css).not.toMatch(/--color-status-\w+-foreground:/);
  });
});
