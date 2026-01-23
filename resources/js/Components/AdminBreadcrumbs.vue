<template>
  <!-- Development warning for missing breadcrumb state -->
  <div v-if="isDevelopment && isInFallbackMode" 
       data-testid="fallback-warning"
       class="min-w-0 flex-1 px-3 py-1 bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-600 rounded text-xs text-yellow-800 dark:text-yellow-200">
    ⚠️ Breadcrumbs in fallback mode - check console for details
  </div>
  
  <!-- Only show breadcrumbs when there are 3+ items (meaningful navigation depth) -->
  <template v-else-if="shouldShowBreadcrumbs">
    <!-- Mobile: Icon button with dropdown showing full trail -->
    <DropdownMenu v-if="isMobile">
      <DropdownMenuTrigger as-child>
        <Button variant="outline" size="icon" class="h-8 w-8 shrink-0">
          <FolderTree class="h-4 w-4" />
          <span class="sr-only">{{ $t('Navigacija') }}</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" class="w-56">
        <DropdownMenuLabel class="text-xs text-muted-foreground">{{ $t('Navigacija') }}</DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuItem 
          v-for="(item, index) in breadcrumbs" 
          :key="index"
          :disabled="index === breadcrumbs.length - 1"
          as-child
        >
          <Link v-if="item.href && index < breadcrumbs.length - 1" :href="item.href" class="flex items-center gap-2">
            <component :is="item.icon" v-if="item.icon" class="h-4 w-4 text-muted-foreground" />
            <Home v-else-if="index === 0" class="h-4 w-4 text-muted-foreground" />
            <span :class="{ 'pl-6': !item.icon && index !== 0 }">{{ $t(item.label) }}</span>
          </Link>
          <span v-else class="flex items-center gap-2 font-medium">
            <component :is="item.icon" v-if="item.icon" class="h-4 w-4" />
            <span :class="{ 'pl-6': !item.icon }">{{ $t(item.label) }}</span>
          </span>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>

    <!-- Desktop: Condensed breadcrumb with collapsible middle items -->
    <Breadcrumb v-else class="min-w-0 flex-1">
      <BreadcrumbList class="flex-nowrap">
        <!-- First item (Home) -->
        <BreadcrumbItem v-if="firstItem">
          <BreadcrumbLink :href="firstItem.href" :prefetch="firstItem.prefetch ?? true">
            <div class="flex items-center gap-1.5">
              <Home class="h-3.5 w-3.5 flex-shrink-0" />
              <span class="sr-only">{{ $t(firstItem.label) }}</span>
            </div>
          </BreadcrumbLink>
        </BreadcrumbItem>

        <!-- Separator after first item -->
        <BreadcrumbSeparator v-if="firstItem" />

        <!-- Middle items in hover card (if more than 3 total items) -->
        <template v-if="middleItems.length > 0">
          <BreadcrumbItem>
            <HoverCard :open-delay="100" :close-delay="200">
              <HoverCardTrigger as-child>
                <button type="button" class="flex items-center gap-1 hover:text-foreground transition-colors cursor-pointer">
                  <BreadcrumbEllipsis class="h-4 w-4" />
                </button>
              </HoverCardTrigger>
              <HoverCardContent align="start" class="w-48 p-1">
                <Link 
                  v-for="(item, index) in middleItems" 
                  :key="index"
                  :href="item.href || '#'"
                  class="flex items-center gap-2 px-2 py-1.5 text-sm rounded-sm hover:bg-accent hover:text-accent-foreground transition-colors"
                >
                  <component :is="item.icon" v-if="item.icon" class="h-4 w-4 text-muted-foreground" />
                  <span>{{ $t(item.label) }}</span>
                </Link>
              </HoverCardContent>
            </HoverCard>
          </BreadcrumbItem>
          <BreadcrumbSeparator />
        </template>

        <!-- Last item (current page) -->
        <BreadcrumbItem v-if="lastItem">
          <BreadcrumbPage>
            <div class="flex items-center gap-1.5">
              <component :is="lastItem.icon" v-if="lastItem.icon" class="h-3.5 w-3.5 flex-shrink-0" />
              <span class="truncate max-w-48 lg:max-w-64">{{ $t(lastItem.label) }}</span>
            </div>
          </BreadcrumbPage>
        </BreadcrumbItem>
      </BreadcrumbList>
    </Breadcrumb>
  </template>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from "laravel-vue-i18n";
import { useBreakpoints, breakpointsTailwind } from '@vueuse/core';
import { Home, FolderTree } from 'lucide-vue-next';
import {
  Breadcrumb,
  BreadcrumbEllipsis,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
  HoverCard,
  HoverCardContent,
  HoverCardTrigger,
} from '@/Components/ui/hover-card';
import { Button } from '@/Components/ui/button';
import { useBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

// Get breadcrumbs from unified state with graceful fallback
const breadcrumbState = useBreadcrumbs();
const { breadcrumbs, context } = breadcrumbState;

// Check if we're in fallback mode
const isInFallbackMode = computed(() => '__isFallback' in breadcrumbState);

// Development mode detection
const isDevelopment = computed(() => import.meta.env.DEV);

// Responsive breakpoint detection
const breakpoints = useBreakpoints(breakpointsTailwind);
const isMobile = breakpoints.smaller('md');

// Only show breadcrumbs when there are 3+ items (meaningful navigation depth)
// 2 items = "Home → Current Page" which duplicates the page title
const shouldShowBreadcrumbs = computed(() => breadcrumbs.value.length >= 3);

// Split breadcrumbs into first, middle, and last for desktop display
const firstItem = computed(() => breadcrumbs.value[0] || null);
const lastItem = computed(() => breadcrumbs.value.length > 1 ? breadcrumbs.value[breadcrumbs.value.length - 1] : null);
const middleItems = computed(() => {
  if (breadcrumbs.value.length <= 2) return [];
  return breadcrumbs.value.slice(1, -1);
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