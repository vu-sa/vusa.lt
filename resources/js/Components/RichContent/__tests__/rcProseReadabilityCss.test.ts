import { globSync, readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

import { describe, expect, it } from 'vitest';

/**
 * Reading-comfort rules for rendered rich content.
 *
 * `.rc-prose` is dropped into hosts that set no typography of their own (ContentPage's
 * `default`/`wide` layouts used to fall through to the 16px/1.5 browser default across
 * a 44rem measure), so the defaults have to live in the shared block rather than on
 * each page. Several of these rules also exist to *outrank* utility classes baked into
 * stored HTML — `.rc-prose` is unlayered, so it wins over Tailwind's utilities layer.
 *
 * jsdom has no CSS cascade or layout, so computed line-height, wrap behaviour and
 * contrast are all unassertable in a component test. This asserts the wiring instead:
 * that the declarations exist in the shared block and are scoped to both the published
 * (`.rc-prose`) and editing (`.rc-prose-editing`) surfaces. What is deliberately NOT
 * covered: whether the resulting colours actually pass WCAG AA (needs a real renderer)
 * and whether `text-wrap: pretty` improves any specific paragraph.
 */
const cssPath = resolve(dirname(fileURLToPath(import.meta.url)), '../../../../css/app.css');

function readCssImportGraph(path: string): string {
  return readFileSync(path, 'utf-8').replace(
    /@import ['"](\.\/[^'"]+\.css)['"];/g,
    (_, importPath: string) => readCssImportGraph(resolve(dirname(path), importPath)),
  );
}

const css = readCssImportGraph(cssPath);

/**
 * Returns the body of the first rule whose selector line matches, brace-balanced and
 * with comments stripped — several of these rules are explained in prose that names
 * the very declaration being asserted against ("break-words, not break-all"), so a
 * negative assertion would otherwise match the comment rather than the CSS.
 */
function ruleBody(selectorPattern: RegExp): string {
  const match = selectorPattern.exec(css);
  if (!match) throw new Error(`No rule matching ${selectorPattern}`);

  const start = css.indexOf('{', match.index);
  let depth = 0;

  for (let i = start; i < css.length; i++) {
    if (css[i] === '{') depth++;
    if (css[i] === '}') {
      depth--;
      if (depth === 0) return css.slice(start + 1, i).replace(/\/\*[\s\S]*?\*\//g, '');
    }
  }

  throw new Error(`Unbalanced braces after ${selectorPattern}`);
}

describe('.rc-prose readability rules', () => {
  // The shared block — everything asserted below must apply to the editing surface
  // too, or the WYSIWYG drifts from the published page.
  const prose = ruleBody(/^\.rc-prose,\s*\n\.rc-prose-editing\s*\{/m);

  it('sets a reading line-height on p and li', () => {
    expect(prose).toMatch(/p,\s*\n\s*li\s*\{\s*line-height:\s*1\.7;/);
  });

  it('opts paragraphs into pretty wrapping and headings into balanced wrapping', () => {
    expect(prose).toMatch(/text-wrap:\s*pretty/);
    expect(prose).toMatch(/:is\(h1, h2, h3, h4, h5, h6\)\s*\{\s*text-wrap:\s*balance;/);
  });

  it('offsets link underlines so they clear Lithuanian ogonek descenders', () => {
    expect(prose).toMatch(/underline-offset-2/);
  });

  it('sets link colour in CSS so stored HTML carrying text-blue-500 is corrected', () => {
    expect(prose).toMatch(/@apply text-brand;/);
  });

  /**
   * Regression guard, generalised. It began as one declaration: `dark:text-vusa-red-on-dark`
   * written inside the nested `a` rule compiled away silently — no error, just an unstyled
   * dark-mode link, because `@apply` with a variant only survives at the top level.
   *
   * The block is now token-driven, so it needs no `dark:` variant at all — `--brand` and
   * `--border` already swap per theme. That makes the guard stronger than it was: any `dark:`
   * reappearing inside this block is either the compile-away trap or a hardcoded colour that
   * should have been a token.
   */
  it('carries no dark: variant inside the shared block — tokens swap themselves', () => {
    expect(prose).not.toMatch(/dark:/);
  });

  it('wraps links with break-words, never break-all', () => {
    expect(prose).toMatch(/\ba\s*\{[^}]*break-words/);
    expect(prose).not.toMatch(/\ba\s*\{[^}]*break-all/);
  });

  it('hangs the blockquote off the brand rule', () => {
    expect(prose).toMatch(/blockquote\s*\{[^}]*border-brand/);
  });

  /**
   * Body copy sits one step back from headings and emphasis — the design's two-weight reading
   * voice. Scoped to the public surface on purpose: `.rc-prose-editing` is also the admin Tiptap
   * root, and muting the text you are typing is the wrong call (and a visible admin change).
   */
  it('uses the dedicated public prose colour while keeping headings and emphasis at full strength', () => {
    expect(css).toMatch(
      /\[data-surface="public"\] :is\(\.rc-prose, \.rc-prose-editing\)\s*\{\s*color:\s*var\(--prose-foreground\);/,
    );
    expect(css).toMatch(
      /\[data-surface="public"\] :is\(\.rc-prose, \.rc-prose-editing\) :is\(h1, h2, h3, h4, h5, h6, strong, b, th\)\s*\{\s*color:\s*var\(--foreground\);/,
    );
  });

  it('spaces consecutive paragraphs inside a blockquote', () => {
    expect(prose).toMatch(/blockquote p\+p\s*\{/);
  });

  it('varies list markers by nesting depth', () => {
    expect(prose).toMatch(/ul ul\s*\{\s*@apply list-\[circle\]/);
    expect(prose).toMatch(/ol ol\s*\{\s*@apply list-\[lower-alpha\]/);
  });

  it('keeps table paragraphs flush — load-bearing for the .tiptap editing surface', () => {
    // tiptap-base.css gives `.tiptap p` a 0.4rem margin; the editing surface matches
    // both selectors, so without this rule cells gain padding only while typing.
    expect(prose).toMatch(/table p\s*\{\s*@apply my-0/);
  });
});

describe('brand colour tokens', () => {
  it('defines a dark-mode-safe red for text', () => {
    expect(css).toMatch(/--color-vusa-red-on-dark:\s*oklch\(/);
  });
});

describe('.typography legacy block', () => {
  it('no longer shatters link text mid-word with break-all', () => {
    const typography = ruleBody(/^\.typography\s*\{/m);

    expect(typography).toMatch(/\ba\s*\{[^}]*break-words/);
    expect(typography).not.toMatch(/break-all/);
  });
});

/**
 * The two independent text scales, and the trap each one hides.
 *
 * jsdom resolves no cascade, so the rendered sizes are checked in a real browser
 * (`.design-reference/probe-a11y-scale.mjs`, `probe-reading-headings.mjs`). What is assertable
 * here is the wiring — and the wiring is exactly what broke twice while building this.
 */
describe('text scaling', () => {
  /**
   * The site-wide setting scales the root font size, so it only reaches `rem`-based type. A
   * `text-[11px]` chip ignores the reader's choice entirely — and the public surface had twenty
   * of them.
   */
  it('sizes public type in rem, never in px', () => {
    const publicFiles = globSync('resources/js/{Components/Public,Pages/Public,Components/RichContent}/**/*.vue', {
      cwd: resolve(dirname(fileURLToPath(import.meta.url)), '../../../../..'),
      absolute: true,
    });

    const offenders = publicFiles.filter(file => /text-\[\d+px\]/.test(readFileSync(file, 'utf-8')));

    expect(offenders).toEqual([]);
  });

  /**
   * A heading has a font-size of its own, so the `calc(1em * scale)` that scales paragraphs does
   * not reach it — and a per-element override would have to restate every level, every breakpoint
   * and every authored `.rc-h-*` size, and would still lose on specificity. Redefining Tailwind's
   * own `--text-*` tokens inside the scope moves all of them at once. Losing this block is how the
   * hierarchy silently inverts at the largest step.
   */
  it('scales headings by redefining the --text-* scale, not per element', () => {
    const readingScale = ruleBody(/^\.reading-scale\s*\{/m);

    expect(readingScale).toMatch(/--text-3xl:\s*calc\(1\.875rem \* var\(--reading-scale, 1\)\)/);
    expect(readingScale).toMatch(/--text-base:\s*calc\(1rem \* var\(--reading-scale, 1\)\)/);
  });

  /** The lead's size comes from a `text-*` utility, so the paragraph rule would scale it twice. */
  it('opts the lead out of the paragraph multiplier', () => {
    expect(ruleBody(/^\.rc-lead>p\s*\{/m)).toMatch(/font-size:\s*inherit/);
  });
});
