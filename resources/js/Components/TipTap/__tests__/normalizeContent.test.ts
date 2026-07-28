import { describe, expect, it } from 'vitest';

import { normalizeContent } from '../normalizeContent';

describe('normalizeContent', () => {
  it('coerces an empty object to a blank string (the new-block case)', () => {
    // A fresh rich-content tiptap/card block stores `json_content` as `{}`. Passing
    // it straight to Tiptap warned "Unknown node type: undefined" because `{}.type`
    // is undefined. This is the regression this helper exists to prevent.
    expect(normalizeContent({})).toBe('');
  });

  it('coerces nullish and empty-string values to a blank string', () => {
    expect(normalizeContent(null)).toBe('');
    expect(normalizeContent('')).toBe('');
  });

  it('coerces a reactive proxy wrapping an empty object (vue reactivity)', () => {
    // The actual warning surfaced with `Proxy { <target>: {} }` — `defineModel`
    // wraps the parent's `{}`. `Object.keys` unwraps the target, so the check holds.
    const emptyProxy = new Proxy({} as Record<string, unknown>, {});
    expect(normalizeContent(emptyProxy)).toBe('');
  });

  it('passes a valid JSON doc through unchanged', () => {
    const doc = { type: 'doc', content: [{ type: 'paragraph' }] };
    expect(normalizeContent(doc)).toBe(doc);
  });

  it('passes an HTML string through unchanged', () => {
    expect(normalizeContent('<p>Hello</p>')).toBe('<p>Hello</p>');
  });
});
