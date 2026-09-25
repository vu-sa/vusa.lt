<template>
  <!-- Phones: one row says where you are and doubles as the section switcher (the tab row would overflow). -->
  <div
    data-slot="mobile-context-bar"
    :class="[
      'shrink-0 border-b border-(--border-opaque) bg-background/90 backdrop-blur-sm md:hidden',
      '[.a11y-contrast_&]:bg-background [.a11y-contrast_&]:backdrop-blur-none',
    ]"
  >
    <div class="flex h-12 items-stretch">
      <button
        data-tour="section-switcher"
        type="button"
        aria-haspopup="dialog"
        :aria-expanded="switcherOpen"
        :class="[barButtonClass, 'min-w-0 flex-1 gap-2 px-4 text-left text-sm']"
        @click="openSwitcher"
      >
        <component :is="activeWorkspace ? workspaceIcon(activeWorkspace.key) : LayoutGrid" class="size-4 shrink-0 text-brand" aria-hidden="true" />
        <span class="min-w-0 truncate">
          <span class="font-bold text-foreground">{{ workspaceLabel }}</span>
          <template v-if="placeLabel">
            <span class="px-1.5 text-muted-foreground" aria-hidden="true">·</span>
            <span class="text-foreground">{{ placeLabel }}</span>
          </template>
        </span>
        <ChevronDown class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
      </button>

      <button
        data-tour="command-palette-mobile"
        type="button"
        :class="[barButtonClass, 'w-12 shrink-0 justify-center border-l border-border text-muted-foreground']"
        :aria-label="$t('shell.chrome.search')"
        @click="toggleSearch"
      >
        <Search class="size-5" aria-hidden="true" />
      </button>
    </div>

    <Sheet v-if="activeWorkspace" v-model:open="switcherOpen">
      <SheetContent side="bottom" class="max-h-[85svh] gap-0 rounded-none pb-[env(safe-area-inset-bottom,0px)]" data-slot="mobile-section-switcher">
        <SheetHeader class="border-b border-border px-4 py-3">
          <SheetTitle class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide">
            <component :is="workspaceIcon(activeWorkspace.key)" class="size-4 shrink-0 text-brand" aria-hidden="true" />
            {{ $t(activeWorkspace.label) }}
          </SheetTitle>
          <SheetDescription class="sr-only">
            {{ $t('shell.chrome.switch_section') }}
          </SheetDescription>
        </SheetHeader>

        <ul class="min-h-0 flex-1 overflow-y-auto overscroll-contain py-1">
          <li
            v-for="(section, index) in activeWorkspace.sections"
            :key="section.key"
            :class="index > 0 && section.startsGroup && 'mt-1 border-t border-border pt-1'"
          >
            <Link
              :href="sectionHref(section)"
              prefetch
              :cache-for="SHELL_PREFETCH_CACHE_FOR"
              v-bind="ariaCurrent(section.key === activeSection?.key)"
              :class="[
                'u-touch flex items-center gap-3 border-l-2 px-4 py-3 text-sm transition-colors hover:text-brand',
                'focus-visible:-outline-offset-2 focus-visible:outline-2 focus-visible:outline-ring',
                section.key === activeSection?.key ? 'border-l-brand-fill font-semibold text-foreground' : 'border-l-transparent text-foreground',
              ]"
              @click="switcherOpen = false"
            >
              <component :is="sectionIcon(section)" class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
              <span class="flex-1">{{ $t(section.label) }}</span>
              <TaskCountBadge v-if="activeWorkspace.key === 'pradzia' && section.key === 'uzduotys'" />
            </Link>
          </li>
        </ul>

        <div class="border-t border-border p-2">
          <Button variant="ghost" class="u-touch w-full justify-between" data-slot="mobile-section-switcher-menu" @click="openMenu">
            {{ $t('shell.chrome.other_workspaces') }}
            <ArrowRight aria-hidden="true" />
          </Button>
        </div>
      </SheetContent>
    </Sheet>
  </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowRight, ChevronDown, LayoutGrid, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import TaskCountBadge from './TaskCountBadge.vue';

import { Button } from '@/Components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/Components/ui/sheet';
import { sectionHref, SHELL_PREFETCH_CACHE_FOR, type AdminSection, type AdminWorkspace } from '@/Composables/useAdminNavigation';
import { useCommandPalette } from '@/Composables/useCommandPalette';
import { useRecordTrail } from '@/Composables/useRecordTrail';
import { sectionIcon } from '@/Constants/adminSections';
import { workspaceIcon } from '@/Constants/adminWorkspaces';
import { ariaCurrent } from '@/Utils/ariaCurrent';

const props = defineProps<{
  activeWorkspace?: AdminWorkspace;
  activeSection?: AdminSection;
}>();

const emit = defineEmits<{ menu: [] }>();

const page = usePage();
const { crumbFor } = useRecordTrail();
const switcherOpen = ref(false);
const { toggle: toggleSearch } = useCommandPalette();

// Both halves of the bar are the same kind of control: flat, full height, one hover.
const barButtonClass = [
  'u-touch flex items-center transition-colors hover:bg-secondary/40 hover:text-foreground',
  'focus-visible:-outline-offset-2 focus-visible:outline-2 focus-visible:outline-ring',
];

const workspaceLabel = computed(() => $t(props.activeWorkspace?.label ?? 'shell.chrome.product'));

// An overview is the workspace itself, so naming it again would only push the useful part off-screen.
const placeLabel = computed(() => {
  const section = props.activeSection;

  if (!section || section.key === 'apzvalga') {
    return null;
  }

  return crumbFor(section.key)?.label ?? $t(section.label);
});

function openSwitcher(): void {
  if (props.activeWorkspace) {
    switcherOpen.value = true;
  }
  else {
    emit('menu');
  }
}

function openMenu(): void {
  switcherOpen.value = false;
  emit('menu');
}

watch(() => page.url, () => {
  switcherOpen.value = false;
});
</script>
