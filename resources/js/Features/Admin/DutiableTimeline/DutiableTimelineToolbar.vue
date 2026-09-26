<template>
  <!--
    Three groups that wrap as units: filters, view controls, then actions. On a phone the
    actions take their own full-width line instead of scattering between the others.
  -->
  <div data-slot="dutiable-timeline-toolbar" class="flex flex-wrap items-center gap-2">
    <div class="flex min-w-0 flex-wrap items-center gap-2" data-tour="timeline-filters">
      <!-- The page names its institution in the title band; the dialog has no band, so the
           heading opens the record here instead. -->
      <template v-if="showScope">
        <Link v-if="scopeHref" :href="scopeHref" class="truncate text-sm font-semibold hover:underline">
          {{ scope?.label ?? '—' }}
        </Link>
        <h2 v-else class="truncate text-sm font-semibold">
          {{ scope?.label ?? '—' }}
        </h2>
        <span v-if="scope?.sublabel" class="shrink-0 text-xs text-muted-foreground">
          {{ scope.sublabel }}
        </span>
      </template>

      <DutiableTimelineFilterMenu
        :label="$t('dutiables.timeline.filters.cadence')"
        :options="cadenceOptions"
        :model-value="cadenceIds"
        @update:model-value="emit('update:cadenceIds', $event)"
      >
        <!-- Marked while ended periods are *hidden*: that is the state where the chart is
             quietly leaving rows out, and the one you need to know about from outside the
             menu. Showing them is the default and hides nothing. -->
        <template #indicator>
          <EyeOff
            v-if="!includeEnded"
            class="size-3 text-status-attention"
            :aria-label="$t('dutiables.timeline.ended_hidden')"
          />
        </template>

        <!-- "Show ended" narrows the same set the term list does, so it lives in the same
             menu rather than as a switch competing with it for the toolbar. -->
        <template #extra>
          <DropdownMenuLabel class="text-xs">
            {{ $t('dutiables.timeline.filters.view') }}
          </DropdownMenuLabel>
          <DropdownMenuCheckboxItem
            :model-value="includeEnded"
            class="text-xs"
            @select="(event: Event) => event.preventDefault()"
            @update:model-value="emit('update:includeEnded', $event)"
          >
            {{ $t('dutiables.timeline.show_ended') }}
          </DropdownMenuCheckboxItem>
        </template>
      </DutiableTimelineFilterMenu>
      <!-- Hidden outright where no assignment records a cross-tenant unit: the menu would
           otherwise offer a single bucket that narrows nothing. -->
      <DutiableTimelineFilterMenu
        v-if="tenantOptions.length > 0"
        :label="$t('dutiables.timeline.filters.tenant')"
        :options="tenantOptions"
        :model-value="tenantKeys"
        @update:model-value="emit('update:tenantKeys', $event)"
      />
    </div>

    <div class="ml-auto flex items-center gap-1.5" data-slot="dutiable-timeline-view-controls">
      <GanttZoomControl
        bordered
        :model-value="monthWidthPx"
        :min="MIN_MONTH_WIDTH"
        :max="MAX_MONTH_WIDTH"
        :step="ZOOM_STEP"
        @update:model-value="emit('update:monthWidthPx', $event)"
      />

      <DutiableTimelineLegend :colors="timelineColors" />

      <slot name="view" />
    </div>

    <div
      v-if="$slots.actions"
      class="flex w-full items-center justify-end gap-2 sm:w-auto"
      data-slot="dutiable-timeline-actions"
    >
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { EyeOff } from 'lucide-vue-next';

import DutiableTimelineFilterMenu, { type FilterOption } from './DutiableTimelineFilterMenu.vue';
import DutiableTimelineLegend from './DutiableTimelineLegend.vue';
import { MAX_MONTH_WIDTH, MIN_MONTH_WIDTH } from './constants';
import type { TimelineColors } from './timelineColors';
import type { TimelineScope } from './types';

import GanttZoomControl from '@/Components/Graphs/GanttZoomControl.vue';
import { DropdownMenuCheckboxItem, DropdownMenuLabel } from '@/Components/ui/dropdown-menu';

/** One slider notch. Eight px is roughly one readable step at either end of the range. */
const ZOOM_STEP = 8;

const props = withDefaults(defineProps<{
  scope: TimelineScope | null;
  /** Off where the page's title band already names the scope. */
  showScope?: boolean;
  includeEnded: boolean;
  monthWidthPx: number;
  timelineColors: TimelineColors;
  cadenceOptions: FilterOption[];
  tenantOptions: FilterOption[];
  cadenceIds: string[];
  tenantKeys: string[];
}>(), {
  showScope: true,
});

const emit = defineEmits<{
  'update:includeEnded': [value: boolean];
  'update:monthWidthPx': [value: number];
  'update:cadenceIds': [value: string[]];
  'update:tenantKeys': [value: string[]];
}>();

/** Whatever the scope names — the heading and the link must not disagree. */
const scopeHref = computed<string | null>(() => {
  if (!props.scope?.id) return null;

  switch (props.scope.type) {
    case 'institution': return route('institutions.show', props.scope.id);
    case 'duty': return route('duties.show', props.scope.id);
    case 'user': return route('users.show', props.scope.id);
    default: return null;
  }
});
</script>
