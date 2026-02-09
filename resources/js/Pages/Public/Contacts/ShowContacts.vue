<template>
  <Head>
    <title>{{ $t('search.institution_page_title') }}</title>
    <meta name="description" :content="$t('search.institution_page_description')">
  </Head>

  <InstitutionSearchInterface
    :initial-query
    :initial-filters
    :type-labels="institutionTypes"
  >
    <!-- Student representatives link slot -->
    <template #after-subtitle>
      <SmartLink
        :href="route('contacts.studentRepresentatives', { subdomain: 'www', lang: $page.props.app.locale })"
        class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-4 py-1.5 text-sm font-medium text-zinc-700 transition-colors hover:bg-vusa-red/10 hover:text-vusa-red dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-vusa-red/20 dark:hover:text-vusa-red"
      >
        {{ $t('VU SA studentų atstovai (-ės)') }} →
      </SmartLink>
    </template>
  </InstitutionSearchInterface>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';

import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import InstitutionSearchInterface from '@/Components/Public/Search/InstitutionSearchInterface.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import IFluentPeople16Regular from '~icons/fluent/people-16-regular';

interface Props {
  institutionTypes?: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
  institutionTypes: () => ({}),
});

const $page = usePage();

// Expose type labels for the search interface
const institutionTypes = computed(() => props.institutionTypes);

// Set breadcrumbs for contacts page
usePageBreadcrumbs(() => {
  return BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem(
      'Kontaktai',
      undefined,
      IFluentPeople16Regular,
    ),
  ]);
});

// Set wider layout for contacts search page
onMounted(() => {
  const page = usePage();
  // This will be picked up by PublicLayout for wider content area
  page.props.layoutWidth = 'content';
});

// Extract initial search parameters from URL
const getUrlParams = () => {
  if (typeof window === 'undefined') return new URLSearchParams();
  return new URLSearchParams(window.location.search);
};

// Initial query from URL
const initialQuery = computed(() => {
  const params = getUrlParams();
  return params.get('q') || '';
});

// Initial filters from URL parameters
const initialFilters = computed(() => {
  const params = getUrlParams();
  const filters: Record<string, any> = {};

  // Parse array parameters (tenants, types)
  const parseArrayParam = (paramName: string, maxItems = 29) => {
    const items = [];
    for (let i = 0; i < maxItems; i++) {
      const value = params.get(`${paramName}[${i}]`);
      if (value) {
        items.push(value);
      }
      else {
        break;
      }
    }
    return items.length > 0 ? items : undefined;
  };

  // Extract filter parameters
  filters.tenants = parseArrayParam('tenants');
  filters.types = parseArrayParam('types');

  return filters;
});
</script>
