<template>
  <PageContent :title="$t('dutiables.timeline.page.title')" :heading-icon="CalendarRange">
    <template #after-heading>
      <!--
        The scope is the single most consequential thing on this page, so the control names
        the institution rather than the action: a button reading "change institution" left
        the current one legible only in the chart's own toolbar.
      -->
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            type="button"
            size="sm"
            variant="outline"
            class="max-w-72 gap-2 font-semibold"
            data-tour="timeline-institution"
          >
            <Building2 class="size-4 shrink-0 text-muted-foreground" />
            <span class="truncate">
              {{ institution?.name ?? $t('dutiables.timeline.page.pick_institution') }}
            </span>
            <ChevronsUpDown class="size-3.5 shrink-0 text-muted-foreground" />
          </Button>
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

      <CollectionSelectDialog
        v-model:open="pickerOpen"
        collection="institutions"
        :title="$t('dutiables.timeline.page.pick_institution')"
        :confirm-label="$t('Pasirinkti')"
        @confirm="onInstitutionSelected"
      />
    </template>

    <p class="mb-4 text-sm text-muted-foreground">
      {{ $t('dutiables.timeline.page.description') }}
    </p>

    <EmptyState
      v-if="!institution"
      :title="$t('dutiables.timeline.page.pick_institution')"
      :description="$t('dutiables.timeline.page.no_scope')"
    />

    <!--
      `max-h`, not `h`: a four-row institution should end after four rows rather than
      reserve a screen, while a forty-row one caps here and scrolls inside the chart.
      Keyed on the institution so switching scope remounts rather than leaving the
      previous chart's staged edits attached to the new one.
    -->
    <div v-else class="flex max-h-[calc(100vh-13rem)] flex-col">
      <DutiableTimelineEditor
        class="min-h-0 flex-auto"
        :key="institution.id"
        scope-type="institution"
        :scope-id="institution.id"
      />
    </div>
  </PageContent>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { Building2, CalendarRange, Check, ChevronsUpDown, Search } from 'lucide-vue-next';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import { EmptyState } from '@/Components/Patterns';
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
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { useProductTour } from '@/Composables/useProductTour';
import { provideTour } from '@/Composables/useTourProvider';
import { InstitutionIconFilled } from '@/Components/icons';

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
  tourId: 'dutiable-timeline-v1',
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

usePageBreadcrumbs(() => [
  BreadcrumbHelpers.createBreadcrumbItem($t('dutiables.timeline.page.title'), undefined, InstitutionIconFilled),
]);
</script>
