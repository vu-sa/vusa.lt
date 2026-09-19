<template>
  <nav
    v-if="trail.length > 0"
    data-slot="shell-breadcrumbs"
    :aria-label="$t('shell.chrome.breadcrumbs')"
    :class="[
      'shrink-0 border-b border-border bg-background px-4 text-xs text-muted-foreground md:px-6',
      !parent?.href && 'max-md:hidden',
    ]"
  >
    <!-- Phone: the way back is the one link that matters, so it is the only thing shown. -->
    <Link
      v-if="parent?.href"
      :href="parent.href"
      prefetch
      class="u-touch -ml-2 flex items-center gap-1 px-2 md:hidden"
    >
      <ChevronLeft class="size-4 shrink-0" aria-hidden="true" />
      <span class="sr-only">{{ $t('shell.chrome.back_to', { page: $t(parent.label) }) }}</span>
      <span class="truncate" aria-hidden="true">{{ $t(parent.label) }}</span>
    </Link>

    <ol class="hidden h-10 items-center gap-2 md:flex">
      <template v-for="(crumb, index) in trail" :key="index">
        <li v-if="index > 0" class="shrink-0 opacity-60" aria-hidden="true">
          /
        </li>
        <li class="min-w-0" :class="index === trail.length - 1 ? 'text-foreground' : 'shrink-0'">
          <Link
            v-if="crumb.href && index < trail.length - 1"
            :href="crumb.href"
            :prefetch="crumb.prefetch ?? true"
            class="transition-colors hover:text-brand"
          >
            {{ $t(crumb.label) }}
          </Link>
          <span v-else class="block truncate" v-bind="ariaCurrent(index === trail.length - 1)">{{ $t(crumb.label) }}</span>
        </li>
      </template>
    </ol>
  </nav>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronLeft } from 'lucide-vue-next';
import { computed } from 'vue';

import { belowSectionTrail, type AdminSection } from '@/Composables/useAdminNavigation';
import { useBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { ariaCurrent } from '@/Utils/ariaCurrent';

const props = defineProps<{
  activeSection?: AdminSection;
}>();

const { breadcrumbs } = useBreadcrumbs();

const trail = computed(() => belowSectionTrail(
  breadcrumbs.value,
  props.activeSection,
  [route('dashboard'), route('administration')],
));

const parent = computed(() => trail.value.slice(0, -1).findLast(crumb => crumb.href));
</script>
