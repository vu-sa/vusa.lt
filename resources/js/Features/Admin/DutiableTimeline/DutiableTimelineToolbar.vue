<template>
  <div data-slot="dutiable-timeline-toolbar" class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex min-w-0 flex-wrap items-center gap-2">
      <h2 class="truncate text-sm font-semibold">
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
      />
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
      <label class="flex items-center gap-2 text-xs text-muted-foreground">
        <Switch :model-value="includeEnded" @update:model-value="emit('update:includeEnded', $event)" />
        {{ $t('dutiables.timeline.show_ended') }}
      </label>

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

      <Button type="button" size="xs" variant="outline" @click="emit('toggle-all')">
        {{ allCollapsed ? $t('dutiables.timeline.expand_all') : $t('dutiables.timeline.collapse_all') }}
      </Button>

      <DutiableTimelineLegend :colors="timelineColors" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ZoomIn, ZoomOut } from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Slider } from '@/Components/ui/slider';
import { Switch } from '@/Components/ui/switch';

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
  allCollapsed: boolean;
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
  'toggle-all': [];
}>();

function step(delta: number): void {
  emit(
    'update:monthWidthPx',
    Math.min(MAX_MONTH_WIDTH, Math.max(MIN_MONTH_WIDTH, props.monthWidthPx + delta)),
  );
}
</script>
