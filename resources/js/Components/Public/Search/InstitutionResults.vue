<template>
  <div class="search-results-container transition-all duration-300 ease-out">
    <!-- Loading State -->
    <div v-if="isLoading" class="min-h-[60vh] sm:min-h-[600px]">
      <div :class="viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 gap-6' : 'space-y-4'">
        <InstitutionResultsSkeleton v-for="i in getSkeletonCount()" :key="i" :view-mode />
      </div>
    </div>

    <!-- Results -->
    <div v-else-if="results.length > 0" class="min-h-[60vh] sm:min-h-[600px]">
      <!-- Grid View -->
      <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <TransitionGroup name="institution-list" appear>
          <NewInstitutionCard 
            v-for="institution in processedResults" 
            :key="institution.id" 
            :institution 
            show-metadata
          />
        </TransitionGroup>
      </div>

      <!-- List View -->
      <div v-else class="space-y-2">
        <TransitionGroup name="institution-list" appear>
          <InstitutionCompactListItem 
            v-for="institution in processedResults" 
            :key="institution.id" 
            :institution 
          />
        </TransitionGroup>
      </div>

      <!-- Load More Button -->
      <div v-if="hasMoreResults" class="flex justify-center pt-8">
        <Button 
          variant="outline" 
          size="lg" 
          :disabled="isLoadingMore"
          class="transition-all duration-200 hover:scale-105 focus:scale-105" 
          @click="emit('loadMore')"
        >
          <template v-if="isLoadingMore">
            <Loader2 class="w-4 h-4 mr-2 animate-spin" />
            {{ $t('search.loading_more') }}
          </template>
          <template v-else>
            <ChevronDown class="w-4 h-4 mr-2" />
            {{ $t('search.show_more_results') }}
          </template>
        </Button>
      </div>
    </div>

    <!-- Empty State for No Results -->
    <div v-else-if="showNoResultsState" class="text-center py-12">
      <div class="max-w-md mx-auto">
        <SearchX class="w-14 h-14 mx-auto text-muted-foreground mb-5" />
        <h3 class="text-lg font-semibold text-foreground mb-3">
          {{ $t('search.no_institutions_found') }}
        </h3>
        <p class="text-muted-foreground mb-6 leading-relaxed">
          {{ $t('search.no_institutions_criteria') }}
        </p>
        <Button variant="outline" @click="emit('clearFilters')">
          <RotateCcw class="w-4 h-4 mr-2" />
          {{ $t('search.clear_filters_action') }}
        </Button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="showEmptyState" class="text-center py-12">
      <div class="max-w-md mx-auto">
        <Building2 class="w-14 h-14 mx-auto text-muted-foreground mb-5" />
        <h3 class="text-lg font-semibold text-foreground mb-3">
          {{ $t('search.browse_institutions') }}
        </h3>
        <p class="text-muted-foreground mb-6 leading-relaxed">
          {{ $t('search.institutions_description') }}
        </p>
        <p class="text-sm text-muted-foreground">
          {{ $t('search.or_browse_all') }} 
          <button class="text-primary hover:underline font-medium" @click="emit('clearFilters')">
            {{ $t('search.browse_all_institutions') }}
          </button>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { usePage } from '@inertiajs/vue3'
import { 
  ChevronDown, 
  Loader2, 
  SearchX, 
  RotateCcw,
  Building2
} from 'lucide-vue-next'

import { Button } from '@/Components/ui/button'
import { Separator } from '@/Components/ui/separator'
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

// Computed states
const showNoResultsState = computed(() => {
  return !props.isLoading && 
         props.results.length === 0 && 
         (props.hasQuery || props.hasActiveFilters)
})

const showEmptyState = computed(() => {
  return !props.isLoading && 
         props.results.length === 0 && 
         !props.hasQuery && 
         !props.hasActiveFilters
})

// Get skeleton count based on view mode
const getSkeletonCount = () => {
  return props.viewMode === 'grid' ? 6 : 4
}
</script>

<style scoped>
.institution-list-enter-active,
.institution-list-leave-active {
  transition: all 0.3s ease;
}

.institution-list-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.institution-list-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.institution-list-move {
  transition: transform 0.3s ease;
}
</style>
