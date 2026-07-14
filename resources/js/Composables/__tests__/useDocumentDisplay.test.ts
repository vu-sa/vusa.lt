import { describe, test, expect } from 'vitest';

import { forceBrowserDocumentUrl } from '../useDocumentDisplay';

describe('forceBrowserDocumentUrl', () => {
  test('appends web=1 to a plain URL', () => {
    expect(forceBrowserDocumentUrl('https://tenant.sharepoint.com/doc/123'))
      .toBe('https://tenant.sharepoint.com/doc/123?web=1');
  });

  test('appends web=1 with & when URL already has a query string', () => {
    expect(forceBrowserDocumentUrl('https://tenant.sharepoint.com/doc/123?e=abc'))
      .toBe('https://tenant.sharepoint.com/doc/123?e=abc&web=1');
  });

  test('is idempotent when URL already contains web=1', () => {
    expect(forceBrowserDocumentUrl('https://tenant.sharepoint.com/doc/123?web=1'))
      .toBe('https://tenant.sharepoint.com/doc/123?web=1');
  });

  test('does not modify URLs that already have download=1', () => {
    expect(forceBrowserDocumentUrl('https://tenant.sharepoint.com/doc/123?download=1'))
      .toBe('https://tenant.sharepoint.com/doc/123?download=1');
  });

  test('returns undefined for null input', () => {
    expect(forceBrowserDocumentUrl(null)).toBeUndefined();
  });

  test('returns undefined for undefined input', () => {
    expect(forceBrowserDocumentUrl(undefined)).toBeUndefined();
  });

  test('returns undefined for empty string', () => {
    expect(forceBrowserDocumentUrl('')).toBeUndefined();
  });
});
