<template>
  <BaseSearchResults
    :results="processedResults"
    :is-loading="isLoading"
    :has-query="hasQuery"
    :search-query="searchQuery"
    :total-hits="totalHits"
    :has-more-results="hasMoreResults"
    :is-loading-more="isLoadingMore"
    :has-active-filters="hasActiveFilters"
    :skeleton-count="getSkeletonCount()"
    :loading-container-class="viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 gap-6' : 'space-y-4'"
    :results-container-class="viewMode === 'grid' ? '' : 'space-y-2'"
    transition-name="institution-list"
    :transition-class="viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6' : 'space-y-2'"
    no-results-title-key="search.no_institutions_found"
    no-results-description-key="search.no_institutions_criteria"
    empty-state-title-key="search.browse_institutions"
    empty-state-description-key="search.institutions_description"
    browse-all-key="search.browse_all_institutions"
    :empty-state-icon="Building2"
    @load-more="emit('loadMore')"
    @clear-filters="emit('clearFilters')"
  >
    <template #skeleton="{ count }">
      <InstitutionResultsSkeleton v-for="i in count" :key="i" :view-mode />
    </template>
    
    <template #item="{ item }">
      <NewInstitutionCard v-if="viewMode === 'grid'" :institution="item" show-metadata />
      <InstitutionCompactListItem v-else :institution="item" />
    </template>
  </BaseSearchResults>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Building2 } from 'lucide-vue-next'

import BaseSearchResults from './Shared/BaseSearchResults.vue'
import NewInstitutionCard from '@/Components/Cards/NewInstitutionCard.vue'
import InstitutionCompactListItem from './InstitutionCompactListItem.vue'
import InstitutionResultsSkeleton from './InstitutionResultsSkeleton.vue'

interface Props {
  results: any[]
  viewMode: 'grid' | 'list'
  isLoading?: boolean
  hasQuery?: boolean
  searchQuery?: string
  totalHits?: number
  hasMoreResults?: boolean
  isLoadingMore?: boolean
  hasActiveFilters?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false,
  hasQuery: false,
  searchQuery: '',
  totalHits: 0,
  hasMoreResults: false,
  isLoadingMore: false,
  hasActiveFilters: false
})

const emit = defineEmits<{
  'loadMore': []
  'clearFilters': []
}>()

// Get current locale
const locale = computed(() => {
  const page = usePage()
  return (page.props.app as any)?.locale || 'lt'
})

// Process results to have locale-aware fields
const processedResults = computed(() => {
  return props.results.map(result => ({
    id: result.id,
    name: locale.value === 'en' ? (result.name_en || result.name_lt) : result.name_lt,
    short_name: locale.value === 'en' ? (result.short_name_en || result.short_name_lt) : result.short_name_lt,
    alias: result.alias,
    email: result.email,
    phone: result.phone,
    website: result.website,
    address: locale.value === 'en' ? (result.address_en || result.address_lt) : result.address_lt,
    image_url: result.image_url,
    logo_url: result.logo_url,
    facebook_url: result.facebook_url,
    instagram_url: result.instagram_url,
    tenant: result.tenant_id ? {
      id: result.tenant_id,
      shortname: result.tenant_shortname,
      alias: result.tenant_alias
    } : null,
    types: (result.type_ids || []).map((id: number, index: number) => ({
      id,
      slug: result.type_slugs?.[index] || '',
      title: locale.value === 'en' 
        ? (result.type_titles_en?.[index] || result.type_titles_lt?.[index] || '')
        : (result.type_titles_lt?.[index] || '')
    })),
    duties_count: result.duties_count || 0,
    has_contacts: result.has_contacts || false
  }))
})

const getSkeletonCount = () => {
  return props.viewMode === 'grid' ? 6 : 4
}
</script>
