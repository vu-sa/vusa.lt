<template>
  <div class="flex items-center justify-between gap-3 mb-2">
    <div class="flex items-center gap-4 min-w-0">
      <!-- Legend toggle button -->
      <button
        v-if="showLegend"
        type="button"
        data-tour="gantt-legend"
        class="flex items-center gap-1.5 text-[11px] text-zinc-600 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 transition-colors"
        @click="emit('show-legend-modal')"
      >
        <span class="inline-block w-2 h-2 rounded-full bg-foreground dark:bg-white" />
        <span>{{ $t('Legenda') }}</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-60" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
        </svg>
      </button>
      <!-- Institution count and active filters -->
      <div class="flex items-center gap-2 text-[11px] text-zinc-600 dark:text-zinc-400 truncate">
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border border-zinc-200 dark:border-zinc-600 bg-white/70 dark:bg-zinc-800/70">{{
          $t('Institucijų') }}: {{ institutionCount }}</span>
        <template v-if="tenantFilter?.length">
          <span class="opacity-70">•</span>
          <div class="flex items-center gap-1 truncate">
            <span class="opacity-70 shrink-0">{{ $t('Filtras') }}:</span>
            <div class="flex items-center gap-1 overflow-hidden">
              <button v-for="tid in tenantFilter" :key="String(tid)"
                type="button"
                class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-600 truncate hover:bg-zinc-200 dark:hover:bg-zinc-600 cursor-pointer transition-colors"
                :title="$t('Slinkti į') + ' ' + (tenantNames[tid] ?? tid)"
                @click="emit('scroll-to-tenant', tid)">
                {{ tenantNames[tid] ?? tid }}
              </button>
            </div>
          </div>
        </template>
        <template v-if="showOnlyWithActivity">
          <span class="opacity-70">•</span>
          <span class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-600">{{ $t('Tik su veikla') }}</span>
        </template>
        <template v-if="showOnlyWithPublicMeetings">
          <span class="opacity-70">•</span>
          <span class="px-1.5 py-0.5 rounded bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-600">{{ $t('Viešos institucijos') }}</span>
        </template>
        <template v-if="meetingsLoading">
          <span class="opacity-70">•</span>
          <span class="inline-flex items-center gap-1.5">
            <svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            {{ $t('visak.gantt.loading_meetings') }}
          </span>
        </template>
      </div>
    </div>
    <div class="flex items-center gap-2 ml-auto">
      <!-- Details toggle - icon button with tooltip -->
      <Tooltip>
        <TooltipTrigger as-child>
          <button type="button"
            class="p-1.5 rounded border text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors"
            :class="detailsExpanded ? 'bg-zinc-100 dark:bg-zinc-700 border-zinc-300 dark:border-zinc-500' : 'border-zinc-200 dark:border-zinc-600'"
            @click="emit('update:detailsExpanded', !detailsExpanded)">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
              <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
            </svg>
          </button>
        </TooltipTrigger>
        <TooltipContent>{{ $t('Išsamios eilutės') }}</TooltipContent>
      </Tooltip>
      <!-- Scale slider -->
      <div data-tour="gantt-scale" class="w-40 flex items-center gap-2 text-[11px] text-zinc-600 dark:text-zinc-400">
        <span class="shrink-0">{{ $t('Mastelis') }}</span>
        <Slider :min="3" :max="36" :step="1" :model-value="[dayWidth]"
          @update:model-value="(values: number[]) => emit('update:dayWidth', values[0])" />
      </div>
      <!-- Fullscreen button - icon with tooltip -->
      <Tooltip v-if="!hideFullscreenButton">
        <TooltipTrigger as-child>
          <button type="button" data-tour="gantt-fullscreen"
            class="p-1.5 rounded border border-zinc-200 dark:border-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-400 transition-colors"
            @click="emit('fullscreen')">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 11-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
          </button>
        </TooltipTrigger>
        <TooltipContent>{{ $t('Visas ekranas') }}</TooltipContent>
      </Tooltip>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import { Slider } from '@/Components/ui/slider';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';

/**
 * MeetingsGanttToolbar
 * - VDOM child extracted from MeetingsGantt so the vapor-compiled parent
 *   avoids nesting reka-ui's Slider/Tooltip (Teleport + `as-child` vnode
 *   cloning) directly — the documented rough-edge case for vapor/vdom
 *   interop. Props/events only, no slots cross the boundary.
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
