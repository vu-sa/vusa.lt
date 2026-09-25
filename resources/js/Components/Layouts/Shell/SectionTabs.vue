<template>
  <nav
    v-if="workspace && workspace.sections.length > 1"
    data-slot="section-tabs"
    :aria-label="$t('shell.chrome.sections_nav')"
    class="shrink-0 border-b border-(--border-opaque) bg-secondary/50 backdrop-blur-sm [.a11y-contrast_&]:bg-background [.a11y-contrast_&]:backdrop-blur-none"
  >
    <div class="mx-auto grid max-w-7xl grid-cols-[minmax(0,1fr)_auto] px-2 sm:px-4 lg:px-6">
      <!-- Tabs that do not fit stay in the flow (so the measurement is stable) but hidden; the
           Daugiau menu lists exactly those. -->
      <ul ref="listRef" class="flex min-w-0 overflow-hidden">
        <li
          v-for="(section, index) in workspace.sections"
          :key="section.key"
          :ref="(el) => setItemRef(el, index)"
          :class="['shrink-0', overflowIndexes.has(index) && 'invisible', startsGroup(section, index) && 'ml-2 border-l border-border pl-2']"
          :aria-hidden="overflowIndexes.has(index) || undefined"
        >
          <!-- On the trail, the tab stands in for its record (the current one, or the way back up);
               the chevron keeps the collection one tap away. -->
          <div v-if="trailCrumb(section)" data-slot="section-tab-crumb" :class="crumbTabClass(section.key === activeSection?.key)">
            <Link
              :href="trailCrumb(section)!.href"
              prefetch
              :cache-for="SHELL_PREFETCH_CACHE_FOR"
              :tabindex="overflowIndexes.has(index) ? -1 : undefined"
              :title="trailCrumb(section)!.title"
              v-bind="ariaCurrent(section.key === activeSection?.key)"
              class="flex min-w-0 items-center gap-2 pr-1 pl-3 transition-colors hover:text-foreground focus-visible:text-foreground"
            >
              <component :is="sectionIcon(section)" class="size-4 shrink-0" aria-hidden="true" />
              <span class="sr-only">{{ $t(section.label) }}:</span>
              <span class="max-w-40 truncate">{{ trailCrumb(section)!.label }}</span>
            </Link>
            <DropdownMenu :modal="false">
              <DropdownMenuTrigger
                data-slot="section-tab-crumb-menu"
                :tabindex="overflowIndexes.has(index) ? -1 : undefined"
                :aria-label="$t('shell.trail.open_section', { section: $t(section.label) })"
                class="flex items-center pr-2 pl-1 transition-colors hover:text-foreground focus-visible:text-foreground pointer-coarse:px-3"
              >
                <ChevronDown class="size-3.5 shrink-0 opacity-60" aria-hidden="true" />
              </DropdownMenuTrigger>
              <DropdownMenuContent align="start" class="w-60 rounded-none p-1 shadow-none">
                <DropdownMenuItem
                  as-child
                  class="cursor-pointer gap-3 rounded-none px-3 py-2.5 text-sm font-medium pointer-coarse:min-h-11"
                >
                  <Link :href="sectionHref(section)" prefetch :cache-for="SHELL_PREFETCH_CACHE_FOR">
                    <component :is="sectionIcon(section)" class="size-4 shrink-0" aria-hidden="true" />
                    <span class="flex-1">{{ $t(section.label) }}</span>
                  </Link>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
          <Link
            v-else
            :href="sectionHref(section)"
            prefetch
            :cache-for="SHELL_PREFETCH_CACHE_FOR"
            :tabindex="overflowIndexes.has(index) ? -1 : undefined"
            v-bind="ariaCurrent(section.key === activeSection?.key)"
            :class="tabClass(section.key === activeSection?.key)"
          >
            <component :is="sectionIcon(section)" class="size-4 shrink-0" aria-hidden="true" />
            {{ $t(section.label) }}
            <TaskCountBadge v-if="hasTaskBadge(section)" />
          </Link>
        </li>
      </ul>

      <DropdownMenu v-if="overflowing.length > 0" :modal="false">
        <DropdownMenuTrigger
          data-slot="section-tabs-more"
          v-bind="ariaCurrent(activeOverflowing !== undefined)"
          :class="tabClass(activeOverflowing !== undefined)"
        >
          <template v-if="activeOverflowing">
            <component :is="sectionIcon(activeOverflowing)" class="size-4 shrink-0" aria-hidden="true" />
            <span class="max-w-40 truncate">{{ trailCrumb(activeOverflowing)?.label ?? $t(activeOverflowing.label) }}</span>
          </template>
          <template v-else>
            <Ellipsis class="size-4 shrink-0" aria-hidden="true" />
            {{ $t('shell.chrome.more_sections') }}
          </template>
          <ChevronDown class="size-3.5 shrink-0 opacity-60" aria-hidden="true" />
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-60 rounded-none p-1 shadow-none">
          <template v-for="(section, index) in overflowing" :key="section.key">
            <DropdownMenuSeparator v-if="index > 0 && section.startsGroup" />
            <DropdownMenuItem
              as-child
              class="cursor-pointer gap-3 rounded-none px-3 py-2.5 text-sm font-medium pointer-coarse:min-h-11"
            >
              <Link
                :href="sectionHref(section)"
                prefetch
                :cache-for="SHELL_PREFETCH_CACHE_FOR"
                v-bind="ariaCurrent(section.key === activeSection?.key)"
              >
                <component :is="sectionIcon(section)" class="size-4 shrink-0" aria-hidden="true" />
                <span class="flex-1">{{ $t(section.label) }}</span>
                <TaskCountBadge v-if="hasTaskBadge(section)" />
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem
              v-if="trailCrumb(section)"
              as-child
              class="cursor-pointer gap-3 rounded-none py-2.5 pr-3 pl-10 text-sm text-muted-foreground pointer-coarse:min-h-11"
            >
              <Link
                :href="trailCrumb(section)!.href"
                prefetch
                :cache-for="SHELL_PREFETCH_CACHE_FOR"
                :title="trailCrumb(section)!.title"
                data-slot="section-tabs-more-crumb"
              >
                <span class="min-w-0 flex-1 truncate">{{ trailCrumb(section)!.label }}</span>
              </Link>
            </DropdownMenuItem>
          </template>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown, Ellipsis } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import TaskCountBadge from './TaskCountBadge.vue';

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
  sectionHref,
  SHELL_PREFETCH_CACHE_FOR,
  type AdminSection,
  type AdminWorkspace,
} from '@/Composables/useAdminNavigation';
import { useOverflowingItems } from '@/Composables/useOverflowingItems';
import { useRecordTrail } from '@/Composables/useRecordTrail';
import { sectionIcon } from '@/Constants/adminSections';
import { ariaCurrent } from '@/Utils/ariaCurrent';

const props = defineProps<{
  workspace?: AdminWorkspace;
  activeSection?: AdminSection;
}>();

const listRef = ref<HTMLElement | null>(null);
const sections = computed(() => props.workspace?.sections ?? []);
const { trail, crumbFor } = useRecordTrail();
// A crumb changes a tab's width, so it has to re-measure what overflows.
const { overflowIndexes, setItemRef } = useOverflowingItems(listRef, () => [sections.value, trail.value]);

const trailCrumb = (section: AdminSection) => crumbFor(section.key);

const overflowing = computed(() => sections.value.filter((_, index) => overflowIndexes.value.has(index)));
const activeOverflowing = computed(() => overflowing.value.find(section => section.key === props.activeSection?.key));

const hasTaskBadge = (section: AdminSection) => props.workspace?.key === 'pradzia' && section.key === 'uzduotys';

const startsGroup = (section: AdminSection, index: number) => index > 0 && section.startsGroup;

const crumbTabClass = (active: boolean) => [
  'flex h-10 items-stretch whitespace-nowrap border-b-2 text-sm font-bold transition-colors pointer-coarse:h-11',
  active
    ? 'border-brand-fill text-foreground'
    : 'border-transparent text-muted-foreground hover:border-brand-fill focus-within:border-brand-fill',
];

const tabClass = (active: boolean) => [
  'flex h-10 items-center gap-2 whitespace-nowrap border-b-2 px-3 text-sm font-bold transition-colors pointer-coarse:h-11',
  active
    ? 'border-brand-fill text-foreground'
    : 'border-transparent text-muted-foreground hover:border-brand-fill hover:text-foreground focus-visible:border-brand-fill',
];
</script>
