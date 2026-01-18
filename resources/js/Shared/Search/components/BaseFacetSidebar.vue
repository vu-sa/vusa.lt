<template>
  <div class="space-y-4">
    <!-- Mobile Filter Button -->
    <div class="lg:hidden mb-4">
      <Sheet v-model:open="isMobileFiltersOpen">
        <SheetTrigger as-child>
          <Button variant="outline" class="w-full">
            <Filter class="w-4 h-4 mr-2" />
            {{ $t('search.filters') }}
            <Badge v-if="activeFilterCount > 0" variant="secondary" class="ml-2">
              {{ activeFilterCount }}
            </Badge>
          </Button>
        </SheetTrigger>
        <SheetContent side="left" class="w-[300px] sm:w-[350px] px-6">
          <SheetHeader class="border-b border-border/20 pb-4 px-0">
            <div class="text-left">
              <SheetTitle class="text-xl font-bold text-foreground flex items-center gap-2">
                <Filter class="h-5 w-5 text-primary" />
                {{ $t(mobileTitle) }}
              </SheetTitle>
            </div>
          </SheetHeader>
          <ScrollArea class="h-full pr-4">
            <div class="mt-8 pb-32">
              <div class="space-y-6">
                <!-- Filter Header -->
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold">
                    {{ $t('search.filters') }}
                  </h3>
                  <Button variant="ghost" size="sm" :disabled="activeFilterCount === 0" class="text-xs"
                    @click="handleClearFilters">
                    <RotateCcw class="w-3 h-3 mr-1" />
                    <template v-if="activeFilterCount > 0">
                      {{ $t('search.clear_filters_count', { count: activeFilterCount }) }}
                    </template>
                    <template v-else>
                      {{ $t('search.clear_filters') }}
                    </template>
                  </Button>
                </div>

                <!-- Mobile Filters Slot -->
                <slot name="mobile-filters" />
              </div>
            </div>
          </ScrollArea>
        </SheetContent>
      </Sheet>
    </div>

    <!-- Desktop Filters -->
    <div class="hidden lg:block">
      <div class="space-y-4">
        <!-- Filter Header -->
        <div class="flex items-start justify-between pt-3 pb-5">
          <div>
            <h3 class="text-xl font-bold text-foreground tracking-tight">
              {{ $t('search.filters') }}
            </h3>
          </div>
          <Button variant="ghost" size="sm" :disabled="activeFilterCount === 0"
            class="text-xs font-medium shrink-0 ml-4" @click="handleClearFilters">
            <RotateCcw class="w-3 h-3 mr-1.5" />
            <template v-if="activeFilterCount > 0">
              {{ $t('search.clear_filters_count', { count: activeFilterCount }) }}
            </template>
            <template v-else>
              {{ $t('search.clear_filters') }}
            </template>
          </Button>
        </div>

        <!-- Desktop Filters Slot -->
        <slot name="desktop-filters" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import {
  Sheet,
  SheetTrigger,
  SheetContent,
  SheetHeader,
  SheetTitle
} from '@/Components/ui/sheet'
import { ScrollArea } from '@/Components/ui/scroll-area'
import { Filter, RotateCcw } from 'lucide-vue-next'

interface Props {
  activeFilterCount?: number
  /** Translation key for mobile sheet title */
  mobileTitle?: string
}

withDefaults(defineProps<Props>(), {
  activeFilterCount: 0,
  mobileTitle: 'search.filters'
})

const emit = defineEmits<{
  clearFilters: []
}>()

const isMobileFiltersOpen = ref(false)

const handleClearFilters = () => {
  emit('clearFilters')
  isMobileFiltersOpen.value = false
}

// Expose for parent components that need to close the sheet
defineExpose({
  closeSheet: () => { isMobileFiltersOpen.value = false }
})
</script>
