/**
 * useGanttSettings - Shared Gantt chart settings with Provide/Inject
 *
 * This composable provides a centralized way to manage Gantt chart settings
 * that need to be shared across multiple components without prop drilling.
 *
 * Settings are persisted to localStorage and synchronized across all consumers.
 *
 * Usage:
 * - In parent (ShowAtstovavimas.vue): call provideGanttSettings()
 * - In children (MeetingsGantt.vue, etc.): call useGanttSettings()
 */
import { computed, inject, provide, type InjectionKey, type Ref } from 'vue';
import { useStorage } from '@vueuse/core';

const STORAGE_KEY = 'gantt-settings';

export interface GanttSettings {
  /** Day width in pixels (zoom level) - default: 3 */
  dayWidthPx: Ref<number>;
  /** Label column width in pixels - default: 220 */
  labelWidth: Ref<number>;
  /** Whether details/rows are expanded - default: false */
  detailsExpanded: Ref<boolean>;
  /** Whether to show duty members on the timeline - default: true */
  showDutyMembers: Ref<boolean>;
  /** Whether to show tenant section headers in the timeline - default: true */
  showTenantHeaders: Ref<boolean>;
  /** Center date timestamp to restore on page load (null = today) */
  centerDateTimestamp: Ref<number | null>;
  /** Vertical scroll position to restore on page load (null = top) */
  verticalScrollPosition: Ref<number | null>;
  /** Update day width */
  setDayWidth: (width: number) => void;
  /** Update label column width */
  setLabelWidth: (width: number) => void;
  /** Toggle details expanded state */
  toggleDetailsExpanded: () => void;
  /** Set the center date to persist */
  setCenterDate: (date: Date | null) => void;
  /** Set the vertical scroll position to persist */
  setVerticalScrollPosition: (position: number | null) => void;
  /** Reset all settings to defaults */
  resetSettings: () => void;
}

interface StoredSettings {
  dayWidthPx?: number;
  labelWidth?: number;
  detailsExpanded?: boolean;
  showDutyMembers?: boolean;
  showTenantHeaders?: boolean;
  centerDateTimestamp?: number | null;
  verticalScrollPosition?: number | null;
}

const GANTT_SETTINGS_KEY: InjectionKey<GanttSettings> = Symbol('gantt-settings');

// Matches useGanttInteractions and the toolbar slider. These three used to disagree
// (4-96 here, 3-36 there), so a stored width could sit outside what the UI could express.
const DEFAULT_DAY_WIDTH = 3;
const MIN_DAY_WIDTH = 1;
const MAX_DAY_WIDTH = 9;

/** A width persisted before MAX_DAY_WIDTH came down would restore a zoom the UI cannot set. */
function clampDayWidth(width: number | undefined): number {
  return Math.max(MIN_DAY_WIDTH, Math.min(MAX_DAY_WIDTH, width ?? DEFAULT_DAY_WIDTH));
}

const DEFAULT_LABEL_WIDTH = 220;
const MIN_LABEL_WIDTH = 100;
const MAX_LABEL_WIDTH = 400;

function createGanttSettingsInstance(defaultShowTenantHeaders: boolean = true): GanttSettings {
  const defaults: StoredSettings = {
    dayWidthPx: DEFAULT_DAY_WIDTH,
    labelWidth: DEFAULT_LABEL_WIDTH,
    detailsExpanded: false,
    showDutyMembers: true,
    showTenantHeaders: defaultShowTenantHeaders,
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
    get: () => clampDayWidth(stored.value.dayWidthPx),
    set: (w) => { stored.value.dayWidthPx = clampDayWidth(w); },
  });

  const labelWidth = computed<number>({
    get: () => stored.value.labelWidth ?? DEFAULT_LABEL_WIDTH,
    set: (w) => { stored.value.labelWidth = Math.max(MIN_LABEL_WIDTH, Math.min(MAX_LABEL_WIDTH, w)); },
  });

  const detailsExpanded = computed<boolean>({
    get: () => stored.value.detailsExpanded ?? false,
    set: (v) => { stored.value.detailsExpanded = v; },
  });

  const showDutyMembers = computed<boolean>({
    get: () => stored.value.showDutyMembers ?? true,
    set: (v) => { stored.value.showDutyMembers = v; },
  });

  const showTenantHeaders = computed<boolean>({
    get: () => stored.value.showTenantHeaders ?? defaultShowTenantHeaders,
    set: (v) => { stored.value.showTenantHeaders = v; },
  });

  const centerDateTimestamp = computed<number | null>({
    get: () => stored.value.centerDateTimestamp ?? null,
    set: (v) => { stored.value.centerDateTimestamp = v; },
  });

  const verticalScrollPosition = computed<number | null>({
    get: () => stored.value.verticalScrollPosition ?? null,
    set: (v) => { stored.value.verticalScrollPosition = v; },
  });

  function resetSettings() {
    stored.value = {
      ...defaults,
      showTenantHeaders: false,
    };
  }

  return {
    dayWidthPx,
    labelWidth,
    detailsExpanded,
    showDutyMembers,
    showTenantHeaders,
    centerDateTimestamp,
    verticalScrollPosition,
    setDayWidth: (w: number) => { dayWidthPx.value = w; },
    setLabelWidth: (w: number) => { labelWidth.value = w; },
    toggleDetailsExpanded: () => { detailsExpanded.value = !detailsExpanded.value; },
    setCenterDate: (date: Date | null) => { centerDateTimestamp.value = date ? date.getTime() : null; },
    setVerticalScrollPosition: (pos: number | null) => { verticalScrollPosition.value = pos; },
    resetSettings,
  };
}

/**
 * Creates and provides Gantt settings to child components.
 * Call this once in the parent component (e.g., ShowAtstovavimas.vue).
 */
export function provideGanttSettings(): GanttSettings {
  const settings = createGanttSettingsInstance(true);
  provide(GANTT_SETTINGS_KEY, settings);

  return settings;
}

/**
 * Injects Gantt settings from the parent component.
 * Call this in child components that need access to shared settings.
 *
 * @throws Error if used outside of a component tree that called provideGanttSettings()
 */
export function useGanttSettings(): GanttSettings {
  const settings = inject(GANTT_SETTINGS_KEY);

  if (!settings) {
    if (import.meta.env.DEV) {
      console.warn('useGanttSettings: No provider found, creating local settings with persistence');
    }
    return createGanttSettingsInstance(false);
  }

  return settings;
}

// Export the injection key for testing purposes
export { GANTT_SETTINGS_KEY };
