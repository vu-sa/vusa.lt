<template>
  <Head>
    <title>Dokumentai - VU SR</title>
    <meta name="description" content="Ieškokite VU SA dokumentų archyve" />
  </Head>
  
  <DocumentSearchInterface
    :initial-query="initialQuery"
    :initial-filters="initialFilters"
  />
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import DocumentSearchInterface from '@/Components/Public/Search/DocumentSearchInterface.vue';

// Props (keeping for backward compatibility but we'll extract what we need)
const props = defineProps<{
  documents?: PaginatedModels<App.Entities.Document>;
  allContentTypes?: App.Entities.Document['content_type'][];
}>();

// Set wider layout for document search page
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

  // Parse array parameters (tenants, contentTypes, language)
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
  filters.contentTypes = parseArrayParam('contentTypes');
  filters.language = parseArrayParam('language', 2);
  
  // Date range filters
  const dateFrom = params.get('dateFrom');
  const dateTo = params.get('dateTo');
  if (dateFrom || dateTo) {
    filters.dateRange = {
      from: dateFrom ? new Date(Number(dateFrom) * 1000) : undefined,
      to: dateTo ? new Date(Number(dateTo) * 1000) : undefined
    };
  }

  return filters;
});
</script>
