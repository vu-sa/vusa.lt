import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

import { describe, expect, it } from 'vitest';

import { createFullExtensions, createRenderExtensions } from '../extensions/presets';

/**
 * Table and embed chrome used to be baked into stored HTML as raw utilities (zinc borders,
 * `rounded-xl shadow-lg`), so it ignored the token palette and the square design. jsdom has no cascade, so this asserts the wiring:
 * only an `rc-table` hook is stored, and the chrome lives in one token-based rule set.
 */
const here = dirname(fileURLToPath(import.meta.url));
const typography = readFileSync(resolve(here, '../../../../css/public/typography.css'), 'utf-8');
const tiptapBase = readFileSync(resolve(here, '../tiptap-base.css'), 'utf-8');
const stripComments = (css: string) => css.replace(/\/\*[\s\S]*?\*\//g, '');

function tableAttributes(extensions: ReturnType<typeof createRenderExtensions>) {
  const tableKit = extensions.find(extension => extension.name === 'tableKit');
  return tableKit?.options as Record<string, { HTMLAttributes?: { class?: string } } | undefined>;
}

describe('rich-text table chrome', () => {
  it('stores only the rc-table hook, never colour utilities', () => {
    for (const options of [tableAttributes(createRenderExtensions()), tableAttributes(createFullExtensions())]) {
      expect(options.table?.HTMLAttributes?.class).toBe('rc-table');
      expect(options.tableCell).toBeUndefined();
      expect(options.tableRow).toBeUndefined();
    }
  });

  it('styles tables with design tokens, including older stored markup', () => {
    const css = stripComments(typography);

    expect(css).toContain('table:is(.rc-table, .border-collapse.table-auto)');
    expect(css).toMatch(/border-collapse border border-border/);
    expect(css).toMatch(/th \{\s*@apply border-border bg-secondary\/50/);
  });

  it('leaves no hard-coded table colours in the editor stylesheet', () => {
    const css = stripComments(tiptapBase);

    expect(css).not.toMatch(/[\s,](th|td)\s*\{[^}]*#[0-9a-f]{3,6}/i);
  });
});

describe('rich-text embed chrome', () => {
  function embedOptions(extensions: ReturnType<typeof createRenderExtensions>) {
    return extensions.find(extension => extension.name === 'youtube')?.options as { nocookie: boolean; HTMLAttributes: { class?: string } };
  }

  it('stores only the rc-embed hook and embeds YouTube in privacy mode', () => {
    for (const options of [embedOptions(createRenderExtensions()), embedOptions(createFullExtensions())]) {
      expect(options.HTMLAttributes.class).toBe('rc-embed');
      expect(options.nocookie).toBe(true);
    }
  });

  // The sanitizer strips `class` from stored iframes, so the frame must key off the element too.
  it('frames embeds square with a hairline, by element as well as by class', () => {
    const css = stripComments(typography);

    expect(css).toContain(':is(.rc-prose, .rc-prose-editing, .tiptap) :is([data-youtube-video] iframe, video)');
    expect(css).toMatch(/rounded-none border border-border bg-secondary shadow-none/);
    expect(stripComments(tiptapBase)).not.toMatch(/iframe\s*\{[^}]*border-radius/);
  });
});

describe('rich-text image chrome', () => {
  it('stores square images and squares older rounded ones', () => {
    const image = createRenderExtensions().find(extension => extension.name === 'image');

    expect((image?.options as { HTMLAttributes: { class: string } }).HTMLAttributes.class).not.toContain('rounded');
    expect(stripComments(typography)).toMatch(/img:is\(\.tiptap-image, \.rounded-md\) \{\s*@apply rounded-none;/);
  });
});
