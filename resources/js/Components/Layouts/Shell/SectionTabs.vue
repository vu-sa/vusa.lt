<template>
  <nav
    v-if="workspace && workspace.sections.length > 1"
    data-slot="section-tabs"
    :aria-label="$t('shell.chrome.sections_nav')"
    class="shrink-0 border-b border-border bg-background"
  >
    <!-- The edge fade only appears on the side that still has tabs beyond it. -->
    <ul
      ref="listRef"
      :class="[
        'flex overflow-x-auto px-2 md:px-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden',
        !arrived.left && !arrived.right && '[mask-image:linear-gradient(to_right,transparent,black_1.5rem,black_calc(100%-1.5rem),transparent)]',
        !arrived.left && arrived.right && '[mask-image:linear-gradient(to_right,transparent,black_1.5rem)]',
        arrived.left && !arrived.right && '[mask-image:linear-gradient(to_right,black_calc(100%-1.5rem),transparent)]',
      ]"
      @pointerdown="markScrolled"
      @wheel.passive="markScrolled"
    >
      <li v-for="section in workspace.sections" :key="section.key" class="shrink-0">
        <Link
          :href="sectionHref(section)"
          prefetch
          :cache-for="SHELL_PREFETCH_CACHE_FOR"
          v-bind="ariaCurrent(section.key === activeSection?.key)"
          :class="[
            'flex h-11 items-center gap-2 whitespace-nowrap border-b-2 px-3 text-xs font-semibold uppercase tracking-wide transition-colors',
            section.key === activeSection?.key
              ? 'border-brand-fill text-foreground'
              : 'border-transparent text-muted-foreground hover:text-foreground',
          ]"
        >
          {{ $t(section.label) }}
          <TaskCountBadge v-if="workspace.key === 'pradzia' && section.key === 'uzduotys'" />
        </Link>
      </li>
    </ul>
  </nav>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useScroll } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

import TaskCountBadge from './TaskCountBadge.vue';

import {
  sectionHref,
  SHELL_PREFETCH_CACHE_FOR,
  type AdminSection,
  type AdminWorkspace,
} from '@/Composables/useAdminNavigation';
import { ariaCurrent } from '@/Utils/ariaCurrent';

const props = defineProps<{
  workspace?: AdminWorkspace;
  activeSection?: AdminSection;
}>();

const listRef = ref<HTMLElement | null>(null);
const { arrivedState: arrived } = useScroll(listRef);

// Centres the active tab by measuring the list itself (`scrollIntoView` would also scroll the
// page). The uppercase labels change width when the web font swaps in, so it re-centres whenever
// a tab resizes — until the user scrolls the row themselves.
let userScrolled = false;
let observer: ResizeObserver | undefined;

function centreActiveTab(): void {
  const list = listRef.value;
  const active = list?.querySelector<HTMLElement>('[aria-current="page"]');

  if (!list || !active) {
    return;
  }

  const offset = active.getBoundingClientRect().left - list.getBoundingClientRect().left + list.scrollLeft;
  list.scrollLeft = offset - (list.clientWidth - active.offsetWidth) / 2;
}

function observeTabs(): void {
  if (typeof ResizeObserver === 'undefined' || !listRef.value) {
    return;
  }

  observer?.disconnect();
  observer ??= new ResizeObserver(() => {
    if (!userScrolled) {
      centreActiveTab();
    }
  });

  for (const tab of listRef.value.children) {
    observer.observe(tab);
  }
}

const markScrolled = () => {
  userScrolled = true;
};

watch(() => [props.workspace?.key, props.activeSection?.key], () => {
  userScrolled = false;
  nextTick(() => {
    centreActiveTab();
    observeTabs();
  });
}, { immediate: true });

onMounted(observeTabs);
onBeforeUnmount(() => observer?.disconnect());
</script>
