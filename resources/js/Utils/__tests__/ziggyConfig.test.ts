import { describe, expect, it, vi } from 'vitest';
import { route, type Config } from 'ziggy-js';

import { ziggyConfig, type ZiggyContext } from '../ziggyConfig';

vi.unmock('ziggy-js');

describe('generated route context', () => {
  const routes: Config = {
    url: 'https://build.invalid',
    port: null,
    defaults: {},
    routes: {
      archive: { uri: '{lang}/{newsString}', methods: ['GET'], domain: '{subdomain}.vusa.test' },
      record: { uri: 'mano/meetings/{meeting}', methods: ['GET'], bindings: { meeting: 'id' } },
    },
  };

  it('uses request locale defaults and subdomains instead of build defaults', () => {
    let context: ZiggyContext = { url: 'https://www.vusa.test', port: null, defaults: { lang: 'lt', newsString: 'naujienos' } };
    const config = ziggyConfig(routes, () => context);
    expect(route('archive', { subdomain: 'mif' }, true, config)).toBe('https://mif.vusa.test/lt/naujienos');

    context = { ...context, defaults: { lang: 'en', newsString: 'news' } };
    expect(route('archive', { subdomain: 'www' }, true, config)).toBe('https://www.vusa.test/en/news');
  });

  it('preserves route model bindings and request ports', () => {
    const config = ziggyConfig(routes, () => ({ url: 'http://www.vusa.test:8080', port: 8080, defaults: {} }));
    expect(route('record', { meeting: { id: 'meeting-123' } }, true, config)).toBe('http://www.vusa.test:8080/mano/meetings/meeting-123');
  });
});
