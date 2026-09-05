import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { TenantType } from '@/Types/enums';
import { localizedSlug } from '@/Utils/LocalizedRoutes';

export interface TenantOption {
  label: string;
  key: string;
  primary_institution?: {
    short_name?: string;
    image_url?: string;
  };
  isMainOffice?: boolean;
}

/**
 * Shared logic behind the "Padalinys" (tenant) switcher — used by the desktop
 * `PadalinysSelector` popover and the mobile drill-down tenant panel, so both stay in sync.
 *
 * Switching tenants is a full page load to a different subdomain, not an Inertia visit,
 * because each tenant is served from its own subdomain.
 */
export function useTenantOptions(prependOptions?: TenantOption[]) {
  const page = usePage();

  const options = computed<TenantOption[]>(() => {
    // Representation units only: PKP tenants are student initiatives and have no public
    // subdomain of their own to switch to. This used to also carry an unexplained
    // `tenant.id <= 17` cut-off, which would have silently hidden the 18th padalinys.
    const tenantOptions = page.props.tenants
      .filter(tenant => tenant.type === TenantType.Padalinys || tenant.type === TenantType.Pagrindinis)
      .map((tenant): TenantOption => ({
        // The unit's name is whatever follows "atstovybė " in its full name — but the main
        // tenant *is* "Vilniaus universiteto Studentų atstovybė", so nothing follows and the
        // label came out empty. Falling back to the shortname gives it "VU SA" instead of a
        // blank row.
        label: $t(tenant.fullname.split('atstovybė ')[1] || tenant.shortname),
        key: tenant.alias,
        primary_institution: tenant.primary_institution
          ? {
              short_name: Array.isArray(tenant.primary_institution.short_name)
                ? tenant.primary_institution.short_name[0]
                : tenant.primary_institution.short_name || undefined,
              image_url: tenant.primary_institution.image_url || undefined,
            }
          : undefined,
        isMainOffice: tenant.type === TenantType.Pagrindinis,
      }));

    return prependOptions ? [...prependOptions, ...tenantOptions] : tenantOptions;
  });

  const isActive = (key: string): boolean => page.props.tenant?.alias === key;

  /**
   * Navigates to another tenant's subdomain, keeping the current path.
   * Handles both production (ff.vusa.lt) and staging (ff.naujas.vusa.lt) hostnames.
   */
  const switchTenant = (key: string | string[]) => {
    let alias: string = Array.isArray(key) ? key[0] ?? '' : key;

    const hostWithoutSubdomain = window.location.host
      .split('.')
      .slice(1)
      .join('.');

    if (alias === 'vusa') {
      alias = 'www';
    }

    window.location.href = `${window.location.protocol}//${alias}.${hostWithoutSubdomain}${usePage().url}`;
  };

  const currentLabel = (mainTenantLabel?: string) => computed(() => {
    if (page.props.tenant?.alias === 'vusa') {
      return $t(mainTenantLabel ?? 'Padaliniai');
    }
    return $t(page.props.tenant?.shortname.split(' ').pop() ?? 'Padaliniai');
  });

  /**
   * Whether tenant switching makes sense on the current page (home, news, contacts).
   *
   * The slugs come from the localized-route registry rather than a hardcoded list, which is
   * how `en/news` came to be missing here in the first place.
   */
  const isSwitchAllowed = computed(() => {
    // Defaulted rather than asserted: an absent path means "not one of the switchable pages",
    // which is the safe answer. It used to throw on `.includes` instead.
    const path = page.props.app?.path ?? '';
    const locales = ['lt', 'en'];

    const newsArchivePaths = locales.map(locale => `${locale}/${localizedSlug('newsArchiveString', locale)}`);

    if ([...locales, ...newsArchivePaths].includes(path)) {
      return true;
    }

    return locales.some(locale => path.includes(localizedSlug('contactsString', locale)));
  });

  return {
    options,
    isActive,
    switchTenant,
    currentLabel,
    isSwitchAllowed,
  };
}
