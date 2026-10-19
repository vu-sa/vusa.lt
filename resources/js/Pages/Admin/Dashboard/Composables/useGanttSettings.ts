/**
 * Gantt display settings shared through provide/inject and persisted to localStorage.
 * Provided once by the page (provideGanttSettings); MeetingsGantt and its wrappers inject them.
 */
import { computed, inject, provide, type InjectionKey, type Ref } from 'vue';
import { useStorage } from '@vueuse/core';

import { DEFAULT_DAY_WIDTH, snapToZoomStop } from '@/Components/Graphs/ganttZoom';

const STORAGE_KEY = 'gantt-settings';

export type GanttGroupBy = 'tenant' | 'type' | 'upcoming' | 'none';

export const GANTT_GROUP_BY_OPTIONS: GanttGroupBy[] = ['tenant', 'type', 'upcoming', 'none'];

export type GanttSortBy = 'name' | 'last_activity' | 'next_meeting' | 'last_meeting';

export const GANTT_SORT_OPTIONS: GanttSortBy[] = ['name', 'last_activity', 'next_meeting', 'last_meeting'];

export interface GanttSettings {
  dayWidthPx: Ref<number>;
  labelWidth: Ref<number>;
  groupBy: Ref<GanttGroupBy>;
  sortBy: Ref<GanttSortBy>;
  /** Center date to restore on page load (null = today) */
  centerDateTimestamp: Ref<number | null>;
  /** Vertical scroll position to restore on page load (null = top) */
  verticalScrollPosition: Ref<number | null>;
  setDayWidth: (width: number) => void;
  setLabelWidth: (width: number) => void;
  setCenterDate: (date: Date | null) => void;
  setVerticalScrollPosition: (position: number | null) => void;
  resetSettings: () => void;
}

interface StoredSettings {
  dayWidthPx?: number;
  labelWidth?: number;
  groupBy?: GanttGroupBy;
  sortBy?: GanttSortBy;
  /** Before grouping by type existed; `false` meant no sections. */
  showTenantHeaders?: boolean;
  centerDateTimestamp?: number | null;
  verticalScrollPosition?: number | null;
}

const GANTT_SETTINGS_KEY: InjectionKey<GanttSettings> = Symbol('gantt-settings');

const DEFAULT_LABEL_WIDTH = 220;
const MIN_LABEL_WIDTH = 100;
const MAX_LABEL_WIDTH = 400;

function createGanttSettingsInstance(defaultGroupBy: GanttGroupBy): GanttSettings {
  const defaults: StoredSettings = {
    dayWidthPx: DEFAULT_DAY_WIDTH,
    labelWidth: DEFAULT_LABEL_WIDTH,
    centerDateTimestamp: null,
    verticalScrollPosition: null,
  };

  const stored = useStorage<StoredSettings>(
    STORAGE_KEY,
    () => ({ ...defaults }),
    undefined,
    { mergeDefaults: true, onError: () => {} },
  );

  const dayWidthPx = computed<number>({
    get: () => snapToZoomStop(stored.value.dayWidthPx),
    set: (w) => { stored.value.dayWidthPx = snapToZoomStop(w); },
  });

  const labelWidth = computed<number>({
    get: () => stored.value.labelWidth ?? DEFAULT_LABEL_WIDTH,
    set: (w) => { stored.value.labelWidth = Math.max(MIN_LABEL_WIDTH, Math.min(MAX_LABEL_WIDTH, w)); },
  });

  const groupBy = computed<GanttGroupBy>({
    get: () => {
      const value = stored.value.groupBy;
      if (value && GANTT_GROUP_BY_OPTIONS.includes(value)) {
        return value;
      }

      return stored.value.showTenantHeaders === false ? 'none' : defaultGroupBy;
    },
    set: (value) => {
      stored.value.groupBy = value;
      delete stored.value.showTenantHeaders;
    },
  });

  const sortBy = computed<GanttSortBy>({
    get: () => {
      const value = stored.value.sortBy;
      return value && GANTT_SORT_OPTIONS.includes(value) ? value : 'name';
    },
    set: (value) => { stored.value.sortBy = value; },
  });

  const centerDateTimestamp = computed<number | null>({
    get: () => stored.value.centerDateTimestamp ?? null,
    set: (v) => { stored.value.centerDateTimestamp = v; },
  });

  const verticalScrollPosition = computed<number | null>({
    get: () => stored.value.verticalScrollPosition ?? null,
    set: (v) => { stored.value.verticalScrollPosition = v; },
  });

  return {
    dayWidthPx,
    labelWidth,
    groupBy,
    sortBy,
    centerDateTimestamp,
    verticalScrollPosition,
    setDayWidth: (w: number) => { dayWidthPx.value = w; },
    setLabelWidth: (w: number) => { labelWidth.value = w; },
    setCenterDate: (date: Date | null) => { centerDateTimestamp.value = date ? date.getTime() : null; },
    setVerticalScrollPosition: (pos: number | null) => { verticalScrollPosition.value = pos; },
    resetSettings: () => { stored.value = { ...defaults }; },
  };
}

/** Call once in the page that hosts the Gantt. */
export function provideGanttSettings(): GanttSettings {
  const settings = createGanttSettingsInstance('tenant');
  provide(GANTT_SETTINGS_KEY, settings);

  return settings;
}

/** Without a provider (standalone use) the chart gets its own persisted, ungrouped settings. */
export function useGanttSettings(): GanttSettings {
  return inject(GANTT_SETTINGS_KEY, null) ?? createGanttSettingsInstance('none');
}

export { GANTT_SETTINGS_KEY };
