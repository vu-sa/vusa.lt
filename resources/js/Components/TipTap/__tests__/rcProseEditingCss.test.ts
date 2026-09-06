import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

import { describe, expect, it } from 'vitest';

/**
 * Regression test for the WYSIWYG heading treatment in the TipTap editing surface.
 *
 * CustomHeading renders authorable heading size/accent/spacing as CSS classes
 * (`rc-h-*`, `rc-h-accent-*`, `rc-h-spacing-*`). The published output wraps content
 * in `.rc-prose`, while the editing surface uses `.rc-prose-editing`. The base flow
 * styles are shared between both contexts, but the heading modifiers used to be
 * scoped only to `.rc-prose`, so spacing/size changes were visible in Preview but
 * not while typing.
 *
 * We can't assert the actual rendered spacing in jsdom (it has no real CSS layout
 * pipeline), so this test asserts the CSS selectors instead — the wiring that must
 * exist for the visual behaviour to be consistent.
 */
const cssPath = resolve(dirname(fileURLToPath(import.meta.url)), '../../../../css/app.css');

function readCssImportGraph(path: string): string {
  return readFileSync(path, 'utf-8').replace(
    /@import ['"](\.\/[^'"]+\.css)['"];/g,
    (_, importPath: string) => readCssImportGraph(resolve(dirname(path), importPath)),
  );
}

const css = readCssImportGraph(cssPath);

const headingModifierClasses = [
  'rc-h-sm',
  'rc-h-md',
  'rc-h-lg',
  'rc-h-xl',
  'rc-h-spacing-tight',
  'rc-h-spacing-loose',
  'rc-h-spacing-none',
];

describe('rc-prose-editing CSS selectors', () => {
  it.each(headingModifierClasses)('includes .rc-prose-editing selector for .%s', (className) => {
    const selectorPattern = new RegExp(
      `\\.rc-prose-editing\\s*(:is\\([^)]+\\))?\\.${className}\\s*[,{]`,
      'm',
    );

    expect(css).toMatch(selectorPattern);
  });
});
