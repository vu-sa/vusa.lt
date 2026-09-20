import { describe, expect, it } from 'vitest';

import { seeAllHref } from '../seeAllHref';

describe('seeAllHref', () => {
  it('has no link when the user may not open the list', () => {
    expect(seeAllHref({ href: null, queryKey: 'q' }, 'test')).toBeNull();
    expect(seeAllHref(undefined, 'test')).toBeNull();
  });

  it('carries the query in the key the destination reads', () => {
    expect(seeAllHref({ href: '/mano/meetings', queryKey: 'q' }, 'senatas')).toBe('/mano/meetings?q=senatas');
    expect(seeAllHref({ href: '/mano/institutions', queryKey: 'search' }, 'senatas')).toBe('/mano/institutions?search=senatas');
  });

  it('appends to a destination that already has a query string', () => {
    expect(seeAllHref({ href: '/mano/search?tab=resources', queryKey: 'q' }, 'a b')).toBe('/mano/search?tab=resources&q=a%20b');
  });

  it('leaves the bare list when there is no query', () => {
    expect(seeAllHref({ href: '/mano/news', queryKey: 'search' }, '  ')).toBe('/mano/news');
  });
});
