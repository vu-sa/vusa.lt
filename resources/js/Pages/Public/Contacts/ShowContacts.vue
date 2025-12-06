<template>
  <Head>
    <title>{{ $t('search.institution_page_title') }}</title>
    <meta name="description" :content="$t('search.institution_page_description')">
  </Head>
  
  <InstitutionSearchInterface
    :initial-query
    :initial-filters
    :type-labels="institutionTypes"
  />
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import InstitutionSearchInterface from '@/Components/Public/Search/InstitutionSearchInterface.vue';

interface Props {
  institutionTypes?: Record<string, string>
}

const props = withDefaults(defineProps<Props>(), {
  institutionTypes: () => ({})
})

// Expose type labels for the search interface
const institutionTypes = computed(() => props.institutionTypes)

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
      } else {
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
