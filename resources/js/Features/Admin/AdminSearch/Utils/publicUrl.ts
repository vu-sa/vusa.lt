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
