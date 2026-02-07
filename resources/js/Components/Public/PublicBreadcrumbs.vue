<template>
  <!-- Development warning for missing breadcrumb state -->
  <div 
    v-if="isDevelopment && isInFallbackMode" 
    data-testid="fallback-warning"
    class="inline-flex items-center gap-2 !px-3 !py-1.5 text-xs bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-600 rounded-lg text-yellow-800 dark:text-yellow-200"
  >
    ⚠️ Breadcrumbs in fallback mode - check console
  </div>
  
  <nav 
    v-else-if="visibleItems.length > 0" 
    aria-label="Breadcrumb"
    class="inline-flex items-center gap-1.5 !px-4 !py-2 text-sm font-medium 
           text-zinc-600 dark:text-zinc-400 
           bg-zinc-100/80 dark:bg-zinc-800/50 
           rounded-lg border border-zinc-200/60 dark:border-zinc-700/50
           backdrop-blur-sm max-w-full overflow-hidden"
  >
    <template v-for="(item, index) in visibleItems" :key="index">
      <!-- Separator before item (except first) -->
      <IFluentChevronRight16Regular 
        v-if="index > 0" 
        class="size-3.5 text-zinc-400 dark:text-zinc-600 flex-shrink-0"
        :class="{ 'hidden sm:block': index === visibleItems.length - 1 && !hasOverflow }"
        aria-hidden="true"
      />
      
      <!-- Ellipsis indicator for overflow (after first item) -->
      <template v-if="hasOverflow && index === 1">
        <span class="text-zinc-400 dark:text-zinc-500 px-0.5 flex-shrink-0">…</span>
        <IFluentChevronRight16Regular 
          class="size-3.5 text-zinc-400 dark:text-zinc-600 flex-shrink-0"
          aria-hidden="true"
        />
      </template>
      
      <!-- Breadcrumb item -->
      <template v-if="item.href && index < visibleItems.length - 1">
        <SmartLink 
          :href="item.href" 
          :prefetch="item.prefetch ?? true"
          class="inline-flex items-center gap-1.5 hover:text-vusa-red transition-colors flex-shrink-0"
        >
          <component 
            :is="item.icon" 
            v-if="item.icon && index === 0" 
            class="size-3.5 flex-shrink-0" 
          />
          <span class="truncate max-w-24 sm:max-w-40">{{ $t(item.label) }}</span>
        </SmartLink>
      </template>
      
      <!-- Current page (last item, no link) -->
      <template v-else>
        <span 
          class="inline-flex items-center gap-1.5 text-zinc-900 dark:text-zinc-200 min-w-0"
          :class="{ 'hidden sm:inline-flex': index === visibleItems.length - 1 && visibleItems.length > 1 }"
          aria-current="page"
        >
          <component 
            :is="item.icon" 
            v-if="item.icon && index === 0" 
            class="size-3.5 flex-shrink-0" 
          />
          <span class="truncate max-w-28 sm:max-w-48">{{ $t(item.label) }}</span>
        </span>
      </template>
    </template>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from "laravel-vue-i18n";
import { useBreakpoints, breakpointsTailwind } from '@vueuse/core';
import { useBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import SmartLink from '@/Components/Public/SmartLink.vue';

// Get breadcrumbs from unified state with graceful fallback
const breadcrumbState = useBreadcrumbs();
const { breadcrumbs } = breadcrumbState;

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
