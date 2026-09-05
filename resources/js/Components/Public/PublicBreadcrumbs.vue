<template>
  <!-- Development warning for missing breadcrumb state -->
  <div
    v-if="isDevelopment && isInFallbackMode"
    data-testid="fallback-warning"
    class="inline-flex items-center gap-2 !px-3 !py-1.5 text-xs border border-brand bg-brand/10 text-brand"
  >
    ⚠️ Breadcrumbs in fallback mode - check console
  </div>

  <nav
    v-else-if="visibleItems.length > 0"
    aria-label="Breadcrumb"
    :class="navClass"
    data-slot="public-breadcrumbs"
  >
    <template v-for="(item, index) in visibleItems" :key="index">
      <!-- Separator before item (except first). A chevron in the bar, a slash inline —
           the inline trail sits inside a title band where a row of icons would compete
           with the eyebrow and chip beside it. -->
      <template v-if="index > 0">
        <span v-if="inline" class="shrink-0 opacity-60" aria-hidden="true">/</span>
        <IFluentChevronRight16Regular
          v-else
          class="size-3.5 shrink-0 text-muted-foreground/60"
          :class="{ 'hidden sm:block': index === visibleItems.length - 1 && !hasOverflow }"
          aria-hidden="true"
        />
      </template>

      <!-- Ellipsis indicator for overflow (after first item) -->
      <template v-if="hasOverflow && index === 1">
        <span class="shrink-0 px-0.5 text-muted-foreground/60">…</span>
        <span v-if="inline" class="shrink-0 opacity-60" aria-hidden="true">/</span>
        <IFluentChevronRight16Regular
          v-else
          class="size-3.5 shrink-0 text-muted-foreground/60"
          aria-hidden="true"
        />
      </template>

      <!-- Breadcrumb item -->
      <template v-if="item.href && index < visibleItems.length - 1">
        <SmartLink
          :href="item.href"
          :prefetch="item.prefetch ?? true"
          class="inline-flex items-center gap-1.5 flex-shrink-0 transition-colors hover:text-brand"
        >
          <component
            :is="item.icon"
            v-if="item.icon && index === 0 && !inline"
            class="size-3.5 flex-shrink-0"
          />
          <span class="truncate max-w-24 sm:max-w-40">{{ $t(item.label) }}</span>
        </SmartLink>
      </template>

      <!-- Current page (last item, no link) -->
      <template v-else>
        <span
          class="inline-flex min-w-0 items-center gap-1.5 text-foreground"
          :class="{ 'hidden sm:inline-flex': index === visibleItems.length - 1 && visibleItems.length > 1 }"
          aria-current="page"
        >
          <component
            :is="item.icon"
            v-if="item.icon && index === 0 && !inline"
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
import { trans as $t } from 'laravel-vue-i18n';
import { useBreakpoints, breakpointsTailwind } from '@vueuse/core';

import { useBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import SmartLink from '@/Components/Public/SmartLink.vue';

const props = withDefaults(defineProps<{
  /**
   * `bar` is the boxed trail `PublicLayout` renders above page content. `inline` is the unboxed
   * one a detail page's title band carries — smaller, slash-separated, and without the leading
   * icon, which would compete with the eyebrow and chip directly below it.
   */
  variant?: 'bar' | 'inline';
}>(), {
  variant: 'bar',
});

const inline = computed(() => props.variant === 'inline');

const navClass = computed(() => (inline.value
  ? 'flex flex-wrap items-center gap-2 text-xs font-medium text-muted-foreground max-w-full'
  // The `!px`/`!py` importants beat the `.wrapper > *` grid padding.
  : 'inline-flex items-center gap-1.5 !px-4 !py-2 text-sm font-medium text-muted-foreground '
  + 'border-y border-border backdrop-blur-sm max-w-full overflow-hidden'));

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
    ...items.slice(-(maxItems - 1)),
  ];
});

const hasOverflow = computed(() => {
  const maxItems = isMobile.value ? maxMobileItems : maxDesktopItems;
  return breadcrumbs.value.length > maxItems;
});
</script>
