<template>
  <div class="min-h-screen bg-background">
    <!-- Header Section -->
    <div class="border-b bg-card/50">
      <div class="container mx-auto px-4 py-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <!-- Title and Description -->
          <div>
            <h1 class="text-2xl font-bold text-foreground tracking-tight">
              {{ title }}
            </h1>
            <p v-if="description" class="text-sm text-muted-foreground mt-1">
              {{ description }}
            </p>
          </div>

          <!-- Search Input -->
          <div class="w-full lg:w-96">
            <div class="relative">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
              <Input
                :model-value="query"
                :placeholder="searchPlaceholder"
                class="pl-10 pr-10"
                @update:model-value="$emit('update:query', $event)"
                @keyup.enter="$emit('search')"
              />
              <button
                v-if="query"
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                @click="$emit('update:query', '')"
              >
                <X class="size-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="container mx-auto px-4 py-6">
      <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar (Desktop) -->
        <aside class="hidden lg:block w-72 shrink-0">
          <div class="sticky top-20">
            <slot name="sidebar" />
          </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 min-w-0">
          <!-- Mobile Filter Button + Sort -->
          <div class="flex items-center justify-between gap-4 mb-4 lg:mb-6">
            <!-- Mobile Filter Button -->
            <div class="lg:hidden">
              <Sheet v-model:open="isMobileFiltersOpen">
                <SheetTrigger as-child>
                  <Button variant="outline" size="sm">
                    <Filter class="size-4 mr-2" />
                    {{ $t('Filtrai') }}
                    <Badge v-if="activeFilterCount > 0" variant="secondary" class="ml-2">
                      {{ activeFilterCount }}
                    </Badge>
                  </Button>
                </SheetTrigger>
                <SheetContent side="left" class="w-[300px] sm:w-[350px] overflow-y-auto">
                  <SheetHeader>
                    <SheetTitle class="flex items-center gap-2">
                      <Filter class="size-5" />
                      {{ $t('Filtrai') }}
                    </SheetTitle>
                  </SheetHeader>
                  <div class="mt-6">
                    <slot name="sidebar" />
                  </div>
                </SheetContent>
              </Sheet>
            </div>

            <!-- Results Count -->
            <div class="flex-1 text-sm text-muted-foreground">
              <span v-if="totalHits > 0">
                {{ $t('Rasta :count rezultatų', { count: totalHits }) }}
              </span>
            </div>

            <!-- Sort Dropdown -->
            <div v-if="sortOptions.length > 0" class="flex items-center gap-2">
              <span class="text-sm text-muted-foreground hidden sm:inline">
                {{ $t('Rikiuoti:') }}
              </span>
              <Select :model-value="sortBy" @update:model-value="$emit('update:sortBy', $event)">
                <SelectTrigger class="w-[180px]">
                  <SelectValue :placeholder="$t('Rikiuoti pagal')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="option in sortOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <!-- Active Filters Pills (if any) -->
          <div v-if="activeFilterCount > 0" class="flex flex-wrap gap-2 mb-4">
            <slot name="active-filters" />
            <Button variant="ghost" size="sm" class="text-xs" @click="$emit('clearFilters')">
              <RotateCcw class="size-3 mr-1" />
              {{ $t('Išvalyti filtrus') }}
            </Button>
          </div>

          <!-- Results Area -->
          <slot name="results" />

          <!-- Load More -->
          <div v-if="hasMoreResults" class="mt-6 text-center">
            <Button
              variant="outline"
              :disabled="isLoadingMore"
              @click="$emit('loadMore')"
            >
              <Loader2 v-if="isLoadingMore" class="size-4 mr-2 animate-spin" />
              {{ $t('Rodyti daugiau') }}
            </Button>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { Search, X, Filter, RotateCcw, Loader2 } from 'lucide-vue-next'

import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Badge } from '@/Components/ui/badge'
import {
  Sheet,
  SheetTrigger,
  SheetContent,
  SheetHeader,
  SheetTitle,
} from '@/Components/ui/sheet'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select'

import type { SortOption } from '../Types/AdminSearchTypes'

interface Props {
  title: string
  description?: string
  query: string
  searchPlaceholder?: string
  totalHits: number
  sortBy: string
  sortOptions: SortOption[]
  activeFilterCount?: number
  hasMoreResults?: boolean
  isLoadingMore?: boolean
}

interface Emits {
  (e: 'update:query', value: string): void
  (e: 'update:sortBy', value: string): void
  (e: 'search'): void
  (e: 'clearFilters'): void
  (e: 'loadMore'): void
}

withDefaults(defineProps<Props>(), {
  description: '',
  searchPlaceholder: 'Ieškoti...',
  activeFilterCount: 0,
  hasMoreResults: false,
  isLoadingMore: false,
})

defineEmits<Emits>()

// Mobile filters sheet state
const isMobileFiltersOpen = ref(false)
</script>
