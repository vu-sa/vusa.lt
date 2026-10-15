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
    v-else-if="items.length > 0"
    aria-label="Breadcrumb"
    class="flex flex-wrap items-center gap-2 text-xs font-medium text-muted-foreground max-w-full"
    data-slot="public-breadcrumbs"
  >
    <template v-for="(item, index) in items" :key="index">
      <!-- Ellipsis for the items hidden after the first one -->
      <span
        v-if="index === 1 && (mobileOverflow || desktopOverflow)"
        data-testid="breadcrumb-ellipsis"
        :class="['inline-flex items-center gap-2', visibilityClass(mobileOverflow, desktopOverflow)]"
      >
        <span class="shrink-0 opacity-60" aria-hidden="true">/</span>
        <span class="shrink-0 px-0.5 text-muted-foreground/60">…</span>
      </span>

      <span
        v-if="isVisibleOnMobile(index) || isVisibleOnDesktop(index)"
        :class="['inline-flex min-w-0 items-center gap-2', visibilityClass(isVisibleOnMobile(index), isVisibleOnDesktop(index))]"
      >
        <span v-if="index > 0" class="shrink-0 opacity-60" aria-hidden="true">/</span>

        <SmartLink
          v-if="item.href && index < items.length - 1"
          :href="item.href"
          :prefetch="item.prefetch ?? true"
          class="inline-flex items-center gap-1.5 flex-shrink-0 transition-colors hover:text-brand"
        >
          <span class="truncate max-w-24 sm:max-w-40">{{ $t(item.label) }}</span>
        </SmartLink>

        <!-- Current page (last item, no link) -->
        <span
          v-else
          class="inline-flex min-w-0 items-center gap-1.5 text-foreground"
          aria-current="page"
        >
          <span class="truncate max-w-28 sm:max-w-48">{{ $t(item.label) }}</span>
        </span>
      </span>
    </template>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { useBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import SmartLink from '@/Components/Public/SmartLink.vue';

withDefaults(defineProps<{
  /** Kept for backwards compatibility across existing pages. */
  variant?: 'inline';
}>(), {
  variant: 'inline',
});

// Get breadcrumbs from unified state with graceful fallback
const breadcrumbState = useBreadcrumbs();
const { breadcrumbs } = breadcrumbState;

// Check if we're in fallback mode
const isInFallbackMode = computed(() => '__isFallback' in breadcrumbState);

// Development mode detection
const isDevelopment = computed(() => import.meta.env.DEV);

// Shortened with CSS rather than a JS breakpoint, so server and client render the same markup.
// Phones keep home and the parent; the current page is already the heading below.
const maxDesktopItems = 5;

const items = computed(() => breadcrumbs.value);

const mobileOverflow = computed(() => items.value.length > 3);
const desktopOverflow = computed(() => items.value.length > maxDesktopItems);

function isVisibleOnMobile(index: number) {
  return index === 0 || (items.value.length > 2 && index === items.value.length - 2);
}

function isVisibleOnDesktop(index: number) {
  return index === 0 || index >= items.value.length - (maxDesktopItems - 1);
}

function visibilityClass(onMobile: boolean, onDesktop: boolean) {
  if (onMobile && onDesktop) return '';
  if (onMobile) return 'sm:hidden';
  if (onDesktop) return 'max-sm:hidden';
  return 'hidden';
}
</script>
