<template>
  <!--
    A workbench fills the screen instead of scrolling: the chart scrolls inside, so the
    toolbar with the save controls never leaves view. The negative margin hands back the
    page measure's bottom padding, which only exists for pages that do scroll.
  -->
  <div
    class="-mb-24 flex min-h-[32rem] flex-col md:-mb-32"
    data-slot="workbench"
    :style="{ height: 'calc(var(--shell-scroll-height, 100svh) - var(--shell-chrome-height, 0px) - var(--shell-bottom-bar, 0px) - 2.5rem)' }"
  >
    <Head :title="$t('dutiables.timeline.page.title')" />
    <header class="flex shrink-0 flex-wrap items-end justify-between gap-3 border-b border-border pb-4">
      <div class="min-w-0">
        <p class="u-eyebrow">{{ $t('dutiables.timeline.page.eyebrow') }}</p>
        <div class="mt-2 flex min-w-0 items-center gap-1">
          <!--
            The scope is the single most consequential thing on this page, so the title is the
            switcher: a separate "change institution" button left the current one legible only
            in the chart's own toolbar.
          -->
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <button
                type="button"
                class="-ml-1 flex min-w-0 items-center gap-2 px-1 py-0.5 text-left hover:bg-accent pointer-coarse:min-h-11"
                data-tour="timeline-institution"
              >
                <h1 class="u-display truncate text-2xl sm:text-3xl">
                  {{ institution?.name ?? $t('dutiables.timeline.page.pick_institution') }}
                </h1>
                <ChevronsUpDown class="size-5 shrink-0 text-muted-foreground" />
              </button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="start" class="w-72">
              <template v-if="userInstitutions.length > 0">
                <DropdownMenuLabel class="text-xs">
                  {{ $t('dutiables.timeline.page.your_institutions') }}
                </DropdownMenuLabel>
                <DropdownMenuItem
                  v-for="own in userInstitutions"
                  :key="own.id"
                  class="text-xs"
                  @select="selectInstitution(own)"
                >
                  <Check :class="['size-3.5', own.id === institution?.id ? 'opacity-100' : 'opacity-0']" />
                  <span class="truncate">{{ own.name }}</span>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
              </template>

              <DropdownMenuItem class="text-xs" @select="pickerOpen = true">
                <Search class="size-3.5" />
                {{ $t('dutiables.timeline.page.search_all') }}
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>

          <Button
            v-if="institution"
            as-child
            size="icon-sm"
            variant="ghost"
            :aria-label="$t('dutiables.timeline.page.open_institution')"
            :title="$t('dutiables.timeline.page.open_institution')"
          >
            <Link :href="route('institutions.show', institution.id)">
              <ArrowUpRight class="size-4" />
            </Link>
          </Button>
        </div>
        <p class="mt-1 text-sm text-muted-foreground">
          {{ $t('dutiables.timeline.page.description') }}
        </p>
      </div>

      <CollectionSelectDialog
        v-model:open="pickerOpen"
        collection="institutions"
        :title="$t('dutiables.timeline.page.pick_institution')"
        :confirm-label="$t('Pasirinkti')"
        @confirm="onInstitutionSelected"
      />
    </header>

    <EmptyState
      v-if="!institution"
      class="mt-4"
      :title="$t('dutiables.timeline.page.pick_institution')"
      :description="$t('dutiables.timeline.page.no_scope')"
    />

    <!-- Keyed on the institution so switching scope remounts rather than leaving the
         previous chart's staged edits attached to the new one. -->
    <FocusModeFrame
      v-else
      v-slot="{ active, toggle }"
      v-model:active="fullscreen"
      class="mt-4 flex min-h-0 flex-1 flex-col"
      :label="$t('dutiables.timeline.fullscreen.region')"
    >
      <DutiableTimelineEditor
        :key="institution.id"
        class="min-h-0 flex-1"
        scope-type="institution"
        :scope-id="institution.id"
        :show-scope="false"
      >
        <template #toolbar-end>
          <Button
            type="button"
            size="icon-sm"
            variant="outline"
            data-tour="timeline-fullscreen"
            :aria-pressed="active"
            :aria-label="active ? $t('dutiables.timeline.fullscreen.exit') : $t('dutiables.timeline.fullscreen.enter')"
            :title="active ? $t('dutiables.timeline.fullscreen.exit') : $t('dutiables.timeline.fullscreen.enter')"
            @click="toggle"
          >
            <Minimize2 v-if="active" class="size-4" />
            <Maximize2 v-else class="size-4" />
          </Button>
        </template>
      </DutiableTimelineEditor>
    </FocusModeFrame>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight, Check, ChevronsUpDown, Maximize2, Minimize2, Search } from 'lucide-vue-next';

import { EmptyState, FocusModeFrame } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { CollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { DutiableTimelineEditor } from '@/Features/Admin/DutiableTimeline';
import { useProductTour } from '@/Composables/useProductTour';
import { provideTour } from '@/Composables/useTourProvider';

interface ScopeInstitution {
  id: string;
  name: string | null;
  alias?: string | null;
}

const props = withDefaults(defineProps<{
  initialInstitution: ScopeInstitution | null;
  /** The actor's own institutions, busiest first — the switcher's shortcuts. */
  userInstitutions?: ScopeInstitution[];
}>(), {
  userInstitutions: () => [],
});

const institution = ref<ScopeInstitution | null>(props.initialInstitution);
const pickerOpen = ref(false);
const fullscreen = ref(false);

/**
 * The server guesses from the actor's own duties, which is right on a first visit and wrong
 * afterwards for anyone who works on a body they hold no seat in. So the last scope wins —
 * but only over the guess, never over an institution named in the URL.
 */
const remembered = useStorage<ScopeInstitution | null>('dutiable-timeline-institution', null, undefined, {
  serializer: {
    read: value => (value ? JSON.parse(value) : null),
    write: value => JSON.stringify(value),
  },
});

function setScope(next: ScopeInstitution): void {
  institution.value = next;
  remembered.value = next;
  pickerOpen.value = false;

  // Keeps the scope in the URL so the view is shareable and survives a reload.
  router.visit(route('dutiables.timeline', { institution: next.id }), {
    preserveState: true,
    preserveScroll: true,
    only: [],
  });
}

function selectInstitution(next: ScopeInstitution): void {
  if (next.id === institution.value?.id) return;

  setScope(next);
}

function onInstitutionSelected(hits: NormalizedSearchHit[]): void {
  const hit = hits[0];
  if (!hit) return;

  // `recordId` is the institution's own id; `id` is the collection-prefixed row key.
  setScope({ id: hit.recordId, name: hit.title });
}

const { startTour, startTourIfNew } = useProductTour({
  tourId: 'dutiable-timeline-v3',
  // A function, so the strings resolve when the tour runs rather than at import time.
  steps: () => [
    {
      popover: {
        title: $t('tutorials.dutiable_timeline.welcome.title'),
        description: $t('tutorials.dutiable_timeline.welcome.description'),
      },
    },
    {
      element: '[data-tour="timeline-institution"]',
      popover: {
        title: $t('tutorials.dutiable_timeline.institution.title'),
        description: $t('tutorials.dutiable_timeline.institution.description'),
      },
    },
    {
      element: '[data-tour="timeline-chart"]',
      popover: {
        title: $t('tutorials.dutiable_timeline.chart.title'),
        description: $t('tutorials.dutiable_timeline.chart.description'),
      },
    },
    {
      element: '[data-tour="timeline-controls"]',
      popover: {
        title: $t('tutorials.dutiable_timeline.controls.title'),
        description: $t('tutorials.dutiable_timeline.controls.description'),
      },
    },
    {
      element: '[data-tour="timeline-filters"]',
      popover: {
        title: $t('tutorials.dutiable_timeline.filters.title'),
        description: $t('tutorials.dutiable_timeline.filters.description'),
      },
    },
    {
      element: '[data-tour="timeline-fullscreen"]',
      popover: {
        title: $t('tutorials.dutiable_timeline.fullscreen.title'),
        description: $t('tutorials.dutiable_timeline.fullscreen.description'),
      },
    },
    {
      element: '[data-tour="timeline-selection"]',
      popover: {
        title: $t('tutorials.dutiable_timeline.selection.title'),
        description: $t('tutorials.dutiable_timeline.selection.description'),
      },
    },
    {
      element: '[data-tour="timeline-suggestions"]',
      popover: {
        title: $t('tutorials.dutiable_timeline.suggestions.title'),
        description: $t('tutorials.dutiable_timeline.suggestions.description'),
      },
    },
    {
      element: '[data-tour="timeline-save"]',
      popover: {
        title: $t('tutorials.dutiable_timeline.save.title'),
        description: $t('tutorials.dutiable_timeline.save.description'),
      },
    },
  ],
});

provideTour(startTour);

/** The chart arrives over the API, so its anchors do not exist on the first frame. */
const TOUR_START_DELAY_MS = 1500;

onMounted(() => {
  const named = new URL(window.location.href).searchParams.has('institution');

  if (!named && remembered.value !== null && remembered.value.id !== institution.value?.id) {
    setScope(remembered.value);
  }

  setTimeout(() => startTourIfNew(), TOUR_START_DELAY_MS);
});

</script>
