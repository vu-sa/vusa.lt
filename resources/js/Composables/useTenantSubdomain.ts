import { usePage } from '@inertiajs/vue3';

/**
 * Resolve the public subdomain for a tenant id using the globally-shared tenant
 * list (`usePage().props.tenants`). The main tenant alias `vusa` maps to `www`;
 * an unknown/missing tenant also falls back to `www`.
 */
export function resolveTenantSubdomain(tenantId?: number): string {
  const alias = usePage().props.tenants?.find(tenant => tenant.id === tenantId)?.alias;

  return !alias || alias === 'vusa' ? 'www' : alias;
}

/** A tenant's public host without scheme, e.g. `mif.vusa.lt` — what permalink fields show before the path. */
export function resolveTenantPublicHost(tenantId?: number): string {
  // app.url is the main tenant's own URL ("https://www.vusa.test"); drop its "www." so the
  // main tenant doesn't double up into "www.www.vusa.test".
  const rootDomain = (usePage().props.app?.url ?? 'https://vusa.lt')
    .replace(/^https?:\/\//, '')
    .replace(/^www\./, '');

  return `${resolveTenantSubdomain(tenantId)}.${rootDomain}`;
}
