import { describe, expect, it } from 'vitest';
import type { Config } from 'ziggy-js';

import { ziggyConfig, type ZiggyContext } from '../ziggyConfig';

// Real ziggy-js ships only in vendor/, which the Frontend Tests CI job does not have,
// so this asserts the config contract rather than route() output.
describe('generated route context', () => {
  const routes: Config = {
    url: 'https://build.invalid',
    port: null,
    defaults: { lang: 'build' },
    routes: {
      archive: { uri: '{lang}/{newsString}', methods: ['GET'], domain: '{subdomain}.vusa.test' },
    },
  };

  it('keeps the generated routes', () => {
    const config = ziggyConfig(routes, () => ({ url: 'https://www.vusa.test', port: null, defaults: {} }));

    expect(config.routes).toBe(routes.routes);
  });

  it('reads url, port and defaults from the request context instead of the build', () => {
    const config = ziggyConfig(routes, () => ({ url: 'http://www.vusa.test:8080', port: 8080, defaults: { lang: 'lt' } }));

    expect(config.url).toBe('http://www.vusa.test:8080');
    expect(config.port).toBe(8080);
    expect(config.defaults).toEqual({ lang: 'lt' });
  });

  it('follows the context when it changes after creation', () => {
    let context: ZiggyContext = { url: 'https://www.vusa.test', port: null, defaults: { lang: 'lt', newsString: 'naujienos' } };
    const config = ziggyConfig(routes, () => context);

    context = { url: 'https://mif.vusa.test', port: null, defaults: { lang: 'en', newsString: 'news' } };

    expect(config.url).toBe('https://mif.vusa.test');
    expect(config.defaults).toEqual({ lang: 'en', newsString: 'news' });
  });
});
