<template>
  <Head>
    <title>{{ $t('search.document_page_title') }}</title>
    <meta name="description" :content="$t('search.document_page_description')">
  </Head>

  <DocumentSearchInterface
    :important-content-types
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
  importantContentTypes?: string[];
}>();

// Set breadcrumbs for documents page
usePageBreadcrumbs(() => {
  return BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem(
      'Dokumentai',
      undefined,
      IFluentDocument16Regular,
    ),
  ]);
});

// Set wider layout for document search page
onMounted(() => {
  const page = usePage();
  // This will be picked up by PublicLayout for wider content area
  page.props.layoutWidth = 'content';
});

// Important content types from settings
const importantContentTypes = computed(() => props.importantContentTypes ?? []);

// URL parameters are now handled by useDocumentSearch composable
// which syncs filters to URL automatically. No need for manual parsing here.
</script>
