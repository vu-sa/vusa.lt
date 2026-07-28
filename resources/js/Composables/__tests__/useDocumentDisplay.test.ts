import { describe, test, expect } from 'vitest';

import { forceBrowserDocumentUrl, isShortcutDocument, getDocumentTargetUrl, type DocumentDisplayItem } from '../useDocumentDisplay';

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

describe('isShortcutDocument', () => {
  test('is true for .url files', () => {
    expect(isShortcutDocument({ name: 'ataskaita2023.vusa.lt.url' })).toBe(true);
  });

  test('is true regardless of case', () => {
    expect(isShortcutDocument({ name: 'ataskaita2023.vusa.lt.URL' })).toBe(true);
  });

  test('is false for other extensions', () => {
    expect(isShortcutDocument({ name: 'protokolas.pdf' })).toBe(false);
  });

  test('is false when name is missing', () => {
    expect(isShortcutDocument({})).toBe(false);
  });
});

describe('getDocumentTargetUrl', () => {
  const baseDocument: DocumentDisplayItem = {
    id: 1,
    title: 'Test',
    anonymous_url: 'https://tenant.sharepoint.com/doc/123',
  };

  test('prefers link_url over share_url and anonymous_url', () => {
    expect(getDocumentTargetUrl({
      ...baseDocument,
      link_url: 'https://ataskaita2023.vusa.lt',
      share_url: 'https://vusa.lt/d/abc123',
    })).toBe('https://ataskaita2023.vusa.lt');
  });

  test('prefers share_url over the raw anonymous_url when link_url is absent', () => {
    expect(getDocumentTargetUrl({
      ...baseDocument,
      link_url: null,
      share_url: 'https://vusa.lt/d/abc123',
    })).toBe('https://vusa.lt/d/abc123');
  });

  test('falls back to anonymous_url with web=1 appended', () => {
    expect(getDocumentTargetUrl({ ...baseDocument, link_url: null, share_url: undefined }))
      .toBe('https://tenant.sharepoint.com/doc/123?web=1');
  });

  test('returns undefined when nothing is available', () => {
    expect(getDocumentTargetUrl({ ...baseDocument, anonymous_url: '', link_url: null, share_url: undefined }))
      .toBeUndefined();
  });
});
