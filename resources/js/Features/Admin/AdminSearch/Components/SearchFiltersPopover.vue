<template>
  <Popover v-model:open="isOpen">
    <PopoverTrigger as-child>
      <Button variant="outline" size="sm">
        <SlidersHorizontal class="mr-2 size-4" />
        {{ $t('Filtrai') }}
        <Badge v-if="activeFilterCount > 0" variant="secondary" class="ml-2 px-1.5 py-0">
          {{ activeFilterCount }}
        </Badge>
      </Button>
    </PopoverTrigger>
    <PopoverContent align="end" class="w-[340px] p-0">
      <div class="max-h-[60vh] overflow-y-auto p-4">
        <AdminFacetSidebar
          :facets="facets"
          :filters="filters"
          :facet-config="facetConfig"
          :is-loading="isLoading"
          :active-filter-count="activeFilterCount"
          @toggle-filter="(field, value) => $emit('toggleFilter', field, value)"
          @set-filter="(field, value) => $emit('setFilter', field, value)"
          @clear-filters="$emit('clearFilters')"
        />
      </div>
      <div class="flex items-center justify-between gap-2 border-t p-3">
        <Button variant="ghost" size="sm" :disabled="activeFilterCount === 0" @click="$emit('clearFilters')">
          {{ $t('Išvalyti') }}
        </Button>
        <Button size="sm" @click="isOpen = false">
          {{ $t('Rodyti rezultatus') }} ({{ totalHits }})
        </Button>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { SlidersHorizontal } from 'lucide-vue-next';

import AdminFacetSidebar from './AdminFacetSidebar.vue';
import type { AdminFacet, AdminSearchFilters, CollectionFacetConfig } from '../Types/AdminSearchTypes';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

defineProps<{
  facets: AdminFacet[];
  filters: AdminSearchFilters;
  facetConfig: CollectionFacetConfig;
  isLoading?: boolean;
  activeFilterCount: number;
  totalHits: number;
}>();

defineEmits<{
  toggleFilter: [field: string, value: string | number];
  setFilter: [field: string, value: unknown];
  clearFilters: [];
}>();

const isOpen = ref(false);
</script>
