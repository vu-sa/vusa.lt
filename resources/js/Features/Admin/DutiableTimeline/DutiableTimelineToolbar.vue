<template>
  <div data-slot="dutiable-timeline-toolbar" class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex min-w-0 flex-wrap items-center gap-2" data-tour="timeline-filters">
      <!-- The heading names a record, so it opens it: reading a term off the chart and then
           hunting for the institution in the sidebar was the commonest detour here. -->
      <Link v-if="scopeHref" :href="scopeHref" class="truncate text-sm font-semibold hover:underline">
        {{ scope?.label ?? '—' }}
      </Link>
      <h2 v-else class="truncate text-sm font-semibold">
        {{ scope?.label ?? '—' }}
      </h2>
      <Badge v-if="scope?.sublabel" variant="secondary" class="shrink-0 text-[10px]">
        {{ scope.sublabel }}
      </Badge>
      <Badge variant="outline" class="shrink-0 text-[10px]">
        {{ $t('dutiables.timeline.row_count', { count: visibleCount }) }}
      </Badge>

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
            class="size-3 text-amber-600 dark:text-amber-400"
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

    <div class="flex flex-wrap items-center gap-3">
      <div class="flex items-center gap-1">
        <Button
          type="button"
          size="icon-xs"
          variant="ghost"
          :disabled="monthWidthPx <= MIN_MONTH_WIDTH"
          :aria-label="$t('dutiables.timeline.zoom.out')"
          @click="step(-ZOOM_STEP)"
        >
          <ZoomOut class="size-3.5" />
        </Button>
        <Slider
          :model-value="[monthWidthPx]"
          :min="MIN_MONTH_WIDTH"
          :max="MAX_MONTH_WIDTH"
          :step="ZOOM_STEP"
          class="w-24"
          :aria-label="$t('dutiables.timeline.zoom.label')"
          @update:model-value="value => value && emit('update:monthWidthPx', value[0])"
        />
        <Button
          type="button"
          size="icon-xs"
          variant="ghost"
          :disabled="monthWidthPx >= MAX_MONTH_WIDTH"
          :aria-label="$t('dutiables.timeline.zoom.in')"
          @click="step(ZOOM_STEP)"
        >
          <ZoomIn class="size-3.5" />
        </Button>
      </div>

      <DutiableTimelineLegend :colors="timelineColors" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { EyeOff, ZoomIn, ZoomOut } from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { DropdownMenuCheckboxItem, DropdownMenuLabel } from '@/Components/ui/dropdown-menu';
import { Slider } from '@/Components/ui/slider';

import DutiableTimelineFilterMenu, { type FilterOption } from './DutiableTimelineFilterMenu.vue';
import DutiableTimelineLegend from './DutiableTimelineLegend.vue';
import { MAX_MONTH_WIDTH, MIN_MONTH_WIDTH } from './constants';
import type { TimelineColors } from './timelineColors';
import type { TimelineScope } from './types';

/** One slider notch. Eight px is roughly one readable step at either end of the range. */
const ZOOM_STEP = 8;

const props = defineProps<{
  scope: TimelineScope | null;
  visibleCount: number;
  includeEnded: boolean;
  monthWidthPx: number;
  timelineColors: TimelineColors;
  cadenceOptions: FilterOption[];
  tenantOptions: FilterOption[];
  cadenceIds: string[];
  tenantKeys: string[];
}>();

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

function step(delta: number): void {
  emit(
    'update:monthWidthPx',
    Math.min(MAX_MONTH_WIDTH, Math.max(MIN_MONTH_WIDTH, props.monthWidthPx + delta)),
  );
}
</script>
