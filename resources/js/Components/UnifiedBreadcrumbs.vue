<template>
  <!-- Development warning for missing breadcrumb state -->
  <div v-if="isDevelopment && isInFallbackMode" 
       data-testid="fallback-warning"
       class="min-w-0 flex-1 px-3 py-1 bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-600 rounded text-xs text-yellow-800 dark:text-yellow-200">
    ⚠️ Breadcrumbs in fallback mode - check console for details
  </div>
  
  <Breadcrumb v-else-if="visibleItems.length > 0" class="min-w-0 flex-1">
    <BreadcrumbList class="flex-wrap">
      <!-- Dynamic items -->
      <template v-for="(item, index) in visibleItems" :key="index">
        <BreadcrumbItem>
          <template v-if="item.href && index < visibleItems.length - 1">
            <BreadcrumbLink :href="item.href">
              <div class="flex items-center gap-1.5">
                <component :is="item.icon" v-if="item.icon" class="h-3.5 w-3.5 flex-shrink-0" />
                <span class="truncate max-w-32 sm:max-w-48">{{ $t(item.label) }}</span>
              </div>
            </BreadcrumbLink>
          </template>
          <template v-else>
            <BreadcrumbPage>
              <div class="flex items-center gap-1.5">
                <component :is="item.icon" v-if="item.icon" class="h-3.5 w-3.5 flex-shrink-0" />
                <span class="truncate max-w-32 sm:max-w-48">{{ $t(item.label) }}</span>
              </div>
            </BreadcrumbPage>
          </template>
        </BreadcrumbItem>

        <BreadcrumbSeparator v-if="index < visibleItems.length - 1" />
      </template>
      
      <!-- Show ellipsis if items are truncated -->
      <template v-if="hasOverflow">
        <BreadcrumbSeparator />
        <BreadcrumbItem>
          <BreadcrumbPage>
            <span class="text-muted-foreground">...</span>
          </BreadcrumbPage>
        </BreadcrumbItem>
      </template>
    </BreadcrumbList>
  </Breadcrumb>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from "laravel-vue-i18n";
import { useBreakpoints, breakpointsTailwind } from '@vueuse/core';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';
import { useBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

// Get breadcrumbs from unified state with graceful fallback
const breadcrumbState = useBreadcrumbs();
const { breadcrumbs, context } = breadcrumbState;

// Check if we're in fallback mode
const isInFallbackMode = computed(() => '__isFallback' in breadcrumbState);

// Development mode detection
const isDevelopment = computed(() => import.meta.env.DEV);

// Responsive breadcrumb logic
const breakpoints = useBreakpoints(breakpointsTailwind);
const isMobile = breakpoints.smaller('sm');

const maxMobileItems = 2;
const maxDesktopItems = 5;

const visibleItems = computed(() => {
  const items = breadcrumbs.value;
  
  // On mobile, show fewer items
  const maxItems = isMobile.value ? maxMobileItems : maxDesktopItems;
  
  if (items.length <= maxItems) {
    return items;
  }
  
  // Show first item and last items
  return [
    ...items.slice(0, 1),
    ...items.slice(-(maxItems - 1))
  ];
});

const hasOverflow = computed(() => {
  const maxItems = isMobile.value ? maxMobileItems : maxDesktopItems;
  return breadcrumbs.value.length > maxItems;
});
</script>

<style scoped>
/* Ensure proper alignment of breadcrumb items */
:deep([data-slot="breadcrumb-link"]),
:deep([data-slot="breadcrumb-page"]) {
  display: inline-flex;
  align-items: center;
}
</style>