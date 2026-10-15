import { computed, type MaybeRefOrGetter, toValue } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import { TenantType } from '@/Types/enums';
import { stripHtmlTags } from '@/Utils/String';

/** Both a public search hit (InstitutionSearchService) and an `Institution` entity fit this shape. */
export interface InstitutionCardData {
  id: string | number;
  name?: string | Array<unknown> | null;
  description?: string | Array<unknown> | null;
  alias?: string | null;
  email?: string | null;
  website?: string | null;
  image_url?: string | null;
  image_focal_point?: string | null;
  logo_url?: string | null;
  facebook_url?: string | null;
  instagram_url?: string | null;
  tenant?: {
    id?: number | null;
    shortname?: string | null;
    alias?: string | null;
    type?: string | null;
  } | null;
  types?: Array<{ id?: number; slug?: string; title?: string }>;
}

const ENTITIES: Record<string, string> = { nbsp: ' ', amp: '&', quot: '"', apos: '\'', lt: '<', gt: '>', '#39': '\'' };

/** Rich descriptions arrive as HTML from entities; cards show them as one plain paragraph. */
export function toPlainText(value: unknown): string | null {
  if (typeof value !== 'string') {
    return null;
  }

  // Block boundaries become spaces so adjacent paragraphs don't fuse into one word.
  const text = stripHtmlTags(value.replace(/<(br|\/(p|div|li|h[1-6]|blockquote|tr|td))\b[^>]*>/gi, ' '))
    .replace(/&(nbsp|amp|quot|apos|lt|gt|#39);/g, (_, entity: string) => ENTITIES[entity] ?? '')
    .replace(/\s+/g, ' ')
    .trim();

  return text || null;
}

/**
 * `omitTypeSlugs` are types the surrounding page already names (a category page, a section
 * heading, an active filter), so repeating them on every card adds nothing.
 */
export function useInstitutionCard(
  institution: MaybeRefOrGetter<InstitutionCardData>,
  href?: MaybeRefOrGetter<string | null | undefined>,
  omitTypeSlugs?: MaybeRefOrGetter<string[] | undefined>,
) {
  const page = usePage();

  const isMainOrPkp = computed(() => {
    const { tenant } = toValue(institution);
    return !tenant || tenant.alias === 'vusa' || tenant.type === TenantType.Pkp;
  });

  const url = computed(() => {
    const explicit = toValue(href);
    if (explicit) {
      return explicit;
    }

    const { id, alias, tenant } = toValue(institution);
    const lang = page.props.app?.locale || 'lt';
    // PKP tenants have no site of their own; their contacts live on www.
    const subdomain = isMainOrPkp.value ? 'www' : (tenant?.alias || resolveTenantSubdomain(tenant?.id ?? undefined));

    return alias
      ? route('contacts.alias', { institution: alias, subdomain, lang })
      : route('contacts.institution', { institution: id, subdomain, lang });
  });

  const eyebrow = computed(() => {
    const { types, tenant } = toValue(institution);
    const omitted = toValue(omitTypeSlugs) ?? [];

    // A PKP tenant's shortname is the project itself, which the title already says.
    return [
      types?.find(type => type.title && !omitted.includes(type.slug ?? ''))?.title,
      tenant?.type === TenantType.Pkp ? null : tenant?.shortname,
    ].filter((part): part is string => Boolean(part));
  });

  const description = computed(() => toPlainText(toValue(institution).description));

  return { url, eyebrow, description };
}
