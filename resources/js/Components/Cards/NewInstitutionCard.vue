<template>
  <article
    class="group flex h-full flex-col border border-border bg-card transition-colors duration-200 hover:border-brand"
    data-slot="institution-card"
  >
    <SmartLink :href="institutionUrl" class="plain flex flex-1 flex-col">
      <div class="relative">
        <MediaFrame
          :src="institution.image_url ?? undefined"
          :alt="String(institution.name || '')"
          :focal-point="institution.image_focal_point"
          :grayscale="false"
          hover-zoom
          class="bg-secondary"
        >
          <template #fallback>
            <IFluentImage24Regular class="size-10 text-muted-foreground/50" />
          </template>
        </MediaFrame>

        <div
          v-if="institution.logo_url"
          class="absolute -bottom-6 left-5 flex size-14 items-center justify-center border border-border bg-card p-1.5"
        >
          <img
            class="size-full object-contain"
            :src="institution.logo_url"
            :alt="`${String(institution.name || '')} logo`"
          >
        </div>
        <div
          v-else
          class="absolute -bottom-6 left-5 flex size-14 items-center justify-center border border-border bg-card"
        >
          <IFluentBuilding24Regular class="size-6 text-muted-foreground" />
        </div>
      </div>

      <div class="flex flex-1 flex-col px-5 pb-5 pt-10">
        <div v-if="showMetadata && ((!hideTenantTag && tenantName) || (!hideTypeTag && displayTypes.length > 0))" class="mb-3 flex flex-wrap items-center gap-1.5">
          <TagChip v-if="!hideTenantTag && tenantName && !isPkp" variant="muted">
            <span class="normal-case tracking-normal font-medium">{{ tenantName }}</span>
          </TagChip>
          <template v-if="!hideTypeTag">
            <TagChip v-for="(type, index) in displayTypes" :key="type.id || index" variant="muted">
              <span class="normal-case tracking-normal font-medium">{{ type.title }}</span>
            </TagChip>
            <span v-if="hasMoreTypes" class="text-xs text-muted-foreground">
              +{{ (institution.types?.length || 0) - 2 }}
            </span>
          </template>
        </div>

        <h3 class="text-pretty text-xl font-bold leading-tight text-foreground transition-colors group-hover:text-brand sm:text-2xl">
          {{ institution.name }}
        </h3>

        <div
          v-if="institution.description"
          class="mt-3 line-clamp-3 text-sm leading-relaxed text-muted-foreground"
          v-html="institution.description"
        />

        <!-- Spacer: pushes separator to bottom but guarantees a minimum gap -->
        <div class="flex-1 min-h-6" />

        <!-- Separator + action row -->
        <div class="border-t border-border pt-4 flex items-center gap-1.5">
          <a
            v-if="institution.facebook_url"
            :href="institution.facebook_url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex size-9 items-center justify-center border border-border bg-transparent text-muted-foreground transition-colors hover:border-brand hover:text-brand"
            @click.stop
          >
            <span class="sr-only">Facebook</span>
            <ISimpleIconsFacebook class="size-3.5" />
          </a>
          <a
            v-if="institution.instagram_url"
            :href="institution.instagram_url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex size-9 items-center justify-center border border-border bg-transparent text-muted-foreground transition-colors hover:border-brand hover:text-brand"
            @click.stop
          >
            <span class="sr-only">Instagram</span>
            <ISimpleIconsInstagram class="size-3.5" />
          </a>
          <span
            :class="[
              'ml-auto inline-flex h-9 items-center gap-1.5 border border-border bg-transparent px-3',
              'text-xs font-bold uppercase tracking-wide text-foreground transition-colors group-hover:border-brand group-hover:text-brand',
            ]"
          >
            {{ $t('Kontaktai') }}
            <IFluentArrowUpRight16Regular class="size-3.5" />
          </span>
        </div>
      </div>
    </SmartLink>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { TenantType } from '@/Types/enums';
import SmartLink from '@/Components/Public/SmartLink.vue';
import { MediaFrame, TagChip } from '@/Components/Public/Base';
import IFluentArrowUpRight16Regular from '~icons/fluent/arrow-up-right-16-regular';
import IFluentBuilding24Regular from '~icons/fluent/building-24-regular';
import IFluentImage24Regular from '~icons/fluent/image24-regular';
import ISimpleIconsFacebook from '~icons/simple-icons/facebook';
import ISimpleIconsInstagram from '~icons/simple-icons/instagram';

// Support both full Institution entity and processed search result structure
interface InstitutionData {
  id: string | number;
  name?: string | Array<unknown> | null;
  short_name?: string | Array<unknown> | null;
  description?: string | Array<unknown> | null;
  alias?: string;
  email?: string | null;
  phone?: string | null;
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
  types?: Array<{
    id?: number;
    slug?: string;
    title?: string;
  }>;
  duties_count?: number;
  has_contacts?: boolean;
}

const props = withDefaults(defineProps<{
  institution: InstitutionData | App.Entities.Institution;
  showMetadata?: boolean;
  hideTenantTag?: boolean;
  hideTypeTag?: boolean;
  href?: string | null;
}>(), {
  href: null,
});

const page = usePage();

// Build institution URL - use provided href or generate from institution data
const institutionUrl = computed(() => {
  // If href is explicitly provided, use it
  if (props.href) {
    return props.href;
  }

  // Generate URL from institution data
  const locale = (page.props.app as { locale?: string })?.locale || 'lt';
  // Use www subdomain for: vusa alias, pkp type tenants, or when no tenant
  const { tenant } = props.institution;
  const subdomain = (!tenant || tenant.alias === 'vusa' || tenant.type === TenantType.Pkp)
    ? 'www'
    : (tenant.alias || 'www');

  // Use alias if available, otherwise use id
  if (props.institution.alias) {
    return route('contacts.alias', {
      institution: props.institution.alias,
      subdomain,
      lang: locale,
    });
  }

  return route('contacts.institution', {
    institution: props.institution.id,
    subdomain,
    lang: locale,
  });
});

// Get tenant name for display
const tenantName = computed(() => {
  return props.institution.tenant?.shortname || null;
});

// Get types to display (max 2)
const displayTypes = computed(() => {
  const types = props.institution.types || [];
  return types.slice(0, 2);
});

// Check if there are more types than displayed
const hasMoreTypes = computed(() => {
  const types = props.institution.types || [];
  return types.length > 2;
});

// Check if this institution belongs to a PKP-type tenant (don't show tenant chip for these)
const isPkp = computed(() => {
  const { tenant, types } = props.institution;
  return tenant?.type === TenantType.Pkp
    || types?.some(t => t.slug === 'pkp')
    || false;
});

</script>
