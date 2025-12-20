<template>
  <Head>
    <title>{{ $t('search.document_page_title') }}</title>
    <meta name="description" :content="$t('search.document_page_description')">
  </Head>
  
  <DocumentSearchInterface
    :initial-query
    :initial-filters
  />
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import DocumentSearchInterface from '@/Components/Public/Search/DocumentSearchInterface.vue';

import IFluentDocument16Regular from '~icons/fluent/document-16-regular';

// Props - now only used for static metadata
const props = defineProps<{
  allContentTypes?: App.Entities.Document['content_type'][];
}>();

// Set breadcrumbs for documents page
usePageBreadcrumbs(() => {
  return BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem(
      'Dokumentai',
      undefined,
      IFluentDocument16Regular
    )
  ]);
});

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
