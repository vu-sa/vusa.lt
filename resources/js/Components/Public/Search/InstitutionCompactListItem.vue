<template>
  <li>
    <!-- Bleeds past the list's own edge on hover (-mx/px, equal and opposite) instead of boxing
         the row — the house idiom for full-width list rows, see .ai/rules/public.md. -->
    <SmartLink
      :href="institutionUrl"
      class="plain group -mx-3 flex items-center gap-3 px-3 py-3 transition-colors hover:bg-secondary/50 sm:-mx-4 sm:gap-4 sm:px-4 sm:py-3.5"
    >
      <!-- Logo/Placeholder -->
      <div class="shrink-0">
        <div
          v-if="institution.logo_url"
          class="size-11 border border-border bg-white overflow-hidden shadow-2xs"
        >
          <img
            :src="institution.logo_url"
            :alt="`${institution.name} logo`"
            class="size-full object-contain p-1"
            loading="lazy"
          >
        </div>
        <div
          v-else
          class="size-11 border border-border bg-muted flex items-center justify-center"
        >
          <IFluentBuilding20Regular class="size-5 text-muted-foreground" />
        </div>
      </div>

      <!-- Name + types -->
      <div class="min-w-0 flex-1">
        <h3 class="truncate text-base font-bold text-foreground transition-colors group-hover:text-brand">
          {{ institution.name }}
        </h3>
        <div v-if="displayTypes.length > 0" class="mt-1 flex flex-wrap items-center gap-1" :title="allTypesText">
          <TagChip v-for="type in displayTypes" :key="type.id ?? type.title" variant="muted" class="max-w-32 sm:max-w-40">
            <span class="truncate normal-case tracking-normal font-medium">{{ type.title }}</span>
          </TagChip>
          <span v-if="moreTypesCount > 0" class="text-xs text-muted-foreground shrink-0">
            +{{ moreTypesCount }}
          </span>
        </div>
      </div>

      <!-- Metadata -->
      <div class="flex shrink-0 items-center gap-2.5">
        <div class="hidden items-center gap-3 sm:flex">
          <!-- Contact Count -->
          <span
            v-if="institution.duties_count && institution.duties_count > 0"
            class="text-xs text-muted-foreground whitespace-nowrap flex items-center gap-1"
          >
            <IFluentPeople20Regular class="size-3.5" />
            {{ institution.duties_count }}
          </span>

          <!-- Social Icons -->
          <div class="flex items-center gap-1">
            <a
              v-if="institution.facebook_url"
              :href="institution.facebook_url"
              target="_blank"
              rel="noopener noreferrer"
              class="text-muted-foreground hover:text-brand transition-colors inline-flex"
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
              class="text-muted-foreground hover:text-brand transition-colors inline-flex"
              @click.stop
            >
              <span class="sr-only">Instagram</span>
              <ISimpleIconsInstagram class="size-3.5" />
            </a>
            <a
              v-if="institution.website"
              :href="institution.website"
              target="_blank"
              rel="noopener noreferrer"
              class="text-muted-foreground hover:text-brand transition-colors inline-flex"
              @click.stop
            >
              <span class="sr-only">{{ $t('Svetainė') }}</span>
              <IFluentGlobe20Regular class="size-3.5" />
            </a>
          </div>
        </div>

        <!-- Tenant Badge -->
        <TagChip v-if="institution.tenant?.shortname" variant="muted" class="shrink-0">
          <span class="normal-case tracking-normal font-medium">{{ institution.tenant.shortname }}</span>
        </TagChip>

        <!-- Arrow -->
        <IFluentArrowRight20Regular class="size-4 text-muted-foreground group-hover:text-brand transition-colors shrink-0" />
      </div>
    </SmartLink>
  </li>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { TenantType } from '@/Types/enums';
import SmartLink from '@/Components/Public/SmartLink.vue';
import { TagChip } from '@/Components/Public/Base';
import IFluentArrowRight20Regular from '~icons/fluent/arrow-right-20-regular';
import IFluentBuilding20Regular from '~icons/fluent/building-20-regular';
import IFluentPeople20Regular from '~icons/fluent/people-20-regular';
import IFluentGlobe20Regular from '~icons/fluent/globe-20-regular';
import ISimpleIconsFacebook from '~icons/simple-icons/facebook';
import ISimpleIconsInstagram from '~icons/simple-icons/instagram';

// Processed institution result structure
interface InstitutionData {
  id: string | number;
  name: string;
  short_name?: string | null;
  description?: string | null;
  alias?: string;
  email?: string | null;
  phone?: string | null;
  website?: string | null;
  address?: string | null;
  image_url?: string | null;
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

const props = defineProps<{
  institution: InstitutionData;
}>();

const page = usePage();
const locale = computed(() => (page.props.app as { locale?: string })?.locale || 'lt');

// Get types to display (max 2)
const displayTypes = computed(() => (props.institution.types || []).slice(0, 2));

const moreTypesCount = computed(() => (props.institution.types?.length || 0) - displayTypes.value.length);

// Text for all types (for tooltip)
const allTypesText = computed(() => (props.institution.types || []).map(t => t.title).join(', '));

// Build institution detail URL
const institutionUrl = computed(() => {
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
      lang: locale.value,
    });
  }

  return route('contacts.institution', {
    institution: props.institution.id,
    subdomain,
    lang: locale.value,
  });
});
</script>
