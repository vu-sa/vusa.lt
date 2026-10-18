<template>
  <div class="mb-2 flex flex-wrap items-center justify-between gap-3" data-slot="meetings-gantt-toolbar">
    <div class="flex min-w-0 flex-wrap items-center gap-2">
      <Badge variant="outline" class="shrink-0 text-xs">
        {{ $t('Institucijų') }}: {{ institutionCount }}
      </Badge>
      <Badge v-if="showOnlyWithActivity" variant="secondary" class="text-xs">
        {{ $t('Tik su veikla') }}
      </Badge>
      <Badge v-if="showOnlyWithPublicMeetings" variant="secondary" class="text-xs">
        <Globe aria-hidden="true" />
        {{ $t('Viešos institucijos') }}
      </Badge>
      <span v-if="meetingsLoading" class="inline-flex items-center gap-1.5 text-xs text-muted-foreground" role="status">
        <LoaderCircle class="size-3.5 animate-spin" aria-hidden="true" />
        {{ $t('visak.gantt.loading_meetings') }}
      </span>
    </div>

    <div class="ml-auto flex items-center gap-1">
      <GanttZoomControl
        data-tour="gantt-scale"
        :model-value="dayWidth"
        :stops="GANTT_ZOOM_STOPS"
        @update:model-value="emit('update:dayWidth', $event)"
      />
      <GanttGroupByMenu :model-value="groupBy" @update:model-value="emit('update:groupBy', $event)" />
      <!-- The legend is too long for a popover, so it keeps its dialog behind the same button. -->
      <Button
        v-if="showLegend"
        type="button"
        size="icon-xs"
        variant="ghost"
        class="pointer-coarse:size-11"
        data-tour="gantt-legend"
        :aria-label="$t('Legenda')"
        @click="emit('show-legend-modal')"
      >
        <Info class="size-3.5" />
      </Button>
      <Tooltip>
        <TooltipTrigger as-child>
          <Button
            type="button"
            size="icon-xs"
            variant="ghost"
            class="pointer-coarse:size-11"
            data-tour="gantt-fullscreen"
            :aria-pressed="fullscreenActive"
            :aria-label="fullscreenActive ? $t('Išeiti iš viso ekrano') : $t('Visas ekranas')"
            @click="emit('fullscreen')"
          >
            <Minimize2 v-if="fullscreenActive" class="size-3.5" />
            <Maximize2 v-else class="size-3.5" />
          </Button>
        </TooltipTrigger>
        <TooltipContent>{{ fullscreenActive ? $t('Išeiti iš viso ekrano') : $t('Visas ekranas') }}</TooltipContent>
      </Tooltip>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Globe, Info, LoaderCircle, Maximize2, Minimize2 } from '@lucide/vue';

import GanttGroupByMenu from './GanttGroupByMenu.vue';
import GanttZoomControl from './GanttZoomControl.vue';
import { GANTT_ZOOM_STOPS } from './ganttZoom';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';
import type { GanttGroupBy } from '@/Pages/Admin/Dashboard/Composables/useGanttSettings';

withDefaults(defineProps<{
  showLegend?: boolean;
  institutionCount: number;
  showOnlyWithActivity?: boolean;
  showOnlyWithPublicMeetings?: boolean;
  dayWidth: number;
  groupBy: GanttGroupBy;
  /** The chart is in focus mode, so the same button leads back out. */
  fullscreenActive?: boolean;
  meetingsLoading?: boolean;
}>(), {
  showLegend: true,
  showOnlyWithActivity: false,
  showOnlyWithPublicMeetings: false,
});

const emit = defineEmits<{
  (e: 'show-legend-modal'): void;
  (e: 'update:dayWidth', payload: number): void;
  (e: 'update:groupBy', payload: GanttGroupBy): void;
  (e: 'fullscreen'): void;
}>();
</script>
