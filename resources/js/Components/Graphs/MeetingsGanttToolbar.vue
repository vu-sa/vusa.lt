<template>
  <div class="mb-2 flex flex-wrap items-center justify-between gap-3" data-slot="meetings-gantt-toolbar">
    <div class="flex min-w-0 flex-wrap items-center gap-2">
      <Badge variant="outline" class="shrink-0 text-xs">
        {{ $t('Institucijų') }}: {{ institutionCount }}
      </Badge>
      <button
        v-for="tid in tenantFilter"
        :key="String(tid)"
        type="button"
        class="max-w-40 truncate border border-border px-1.5 py-0.5 text-xs text-muted-foreground hover:bg-accent hover:text-foreground pointer-coarse:min-h-11"
        :title="$t('Slinkti į') + ' ' + (tenantNames[tid] ?? tid)"
        @click="emit('scroll-to-tenant', tid)"
      >
        {{ tenantNames[tid] ?? tid }}
      </button>
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
        :min="1"
        :max="9"
        :step="2"
        @update:model-value="emit('update:dayWidth', $event)"
      />
      <Tooltip>
        <TooltipTrigger as-child>
          <Button
            type="button"
            size="icon-xs"
            :variant="detailsExpanded ? 'secondary' : 'ghost'"
            class="pointer-coarse:size-11"
            data-slot="gantt-details-toggle"
            :aria-pressed="detailsExpanded"
            :aria-label="$t('Išsamios eilutės')"
            @click="emit('update:detailsExpanded', !detailsExpanded)"
          >
            <Rows3 class="size-3.5" />
          </Button>
        </TooltipTrigger>
        <TooltipContent>{{ $t('Išsamios eilutės') }}</TooltipContent>
      </Tooltip>
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
      <Tooltip v-if="!hideFullscreenButton">
        <TooltipTrigger as-child>
          <Button
            type="button"
            size="icon-xs"
            variant="ghost"
            class="pointer-coarse:size-11"
            data-tour="gantt-fullscreen"
            :aria-label="$t('Visas ekranas')"
            @click="emit('fullscreen')"
          >
            <Maximize2 class="size-3.5" />
          </Button>
        </TooltipTrigger>
        <TooltipContent>{{ $t('Visas ekranas') }}</TooltipContent>
      </Tooltip>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Globe, Info, LoaderCircle, Maximize2, Rows3 } from 'lucide-vue-next';

import GanttZoomControl from './GanttZoomControl.vue';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';

/**
 * VDOM child of the vapor-compiled MeetingsGantt: reka-ui's Slider and Tooltip (Teleport,
 * `as-child` cloning) stay out of the vapor tree. Props and events only, no slots.
 */

withDefaults(defineProps<{
  showLegend?: boolean;
  institutionCount: number;
  tenantFilter?: Array<string | number>;
  tenantNames: Record<string | number, string>;
  showOnlyWithActivity?: boolean;
  showOnlyWithPublicMeetings?: boolean;
  detailsExpanded?: boolean;
  dayWidth: number;
  hideFullscreenButton?: boolean;
  meetingsLoading?: boolean;
}>(), {
  showLegend: true,
  showOnlyWithActivity: false,
  showOnlyWithPublicMeetings: false,
  detailsExpanded: false,
});

const emit = defineEmits<{
  (e: 'show-legend-modal'): void;
  (e: 'scroll-to-tenant', tenantId: string | number): void;
  (e: 'update:detailsExpanded', payload: boolean): void;
  (e: 'update:dayWidth', payload: number): void;
  (e: 'fullscreen'): void;
}>();
</script>
