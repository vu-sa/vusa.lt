import type { Config } from 'ziggy-js';

export type ZiggyContext = Pick<Config, 'url' | 'port' | 'defaults'>;

export function ziggyConfig(routes: Config, context: () => ZiggyContext): Config {
  return {
    routes: routes.routes,
    get url() { return context().url; },
    get port() { return context().port; },
    get defaults() { return context().defaults; },
  };
}
