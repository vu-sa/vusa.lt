import { describe, expect, it } from 'vitest';

import { buildInfix } from '../searchParams';

describe('buildInfix', () => {
  it('marks infix-enabled fields as fallback and others as off', () => {
    // meetings: title + description are infix-enabled; the institution_name_* fields are not.
    expect(buildInfix('title,description,institution_name_lt,institution_name_en', 'meetings'))
      .toBe('fallback,fallback,off,off');
  });

  it('turns off non-infix fields like email', () => {
    expect(buildInfix('name_lt,name_en,short_name_lt,short_name_en,alias,email', 'institutions'))
      .toBe('fallback,fallback,fallback,fallback,fallback,off');
    expect(buildInfix('name_lt,name_en,email,institution_name_lt,institution_name_en', 'duties'))
      .toBe('fallback,fallback,off,fallback,fallback');
  });

  it('enables fallback for fully infix-indexed query_by lists', () => {
    expect(buildInfix('title,short', 'news')).toBe('fallback,fallback');
  });

  it('returns an empty string for unknown collections', () => {
    expect(buildInfix('title', 'unknown_collection')).toBe('');
  });
});
