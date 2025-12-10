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
import { ref, provide, inject, watch, type Ref, type InjectionKey } from 'vue';

const STORAGE_KEY = 'gantt-settings';

export interface GanttSettings {
  /** Day width in pixels (zoom level) - default: 24 */
  dayWidthPx: Ref<number>;
  /** Label column width in pixels - default: 220 */
  labelWidth: Ref<number>;
  /** Whether details/rows are expanded - default: false */
  detailsExpanded: Ref<boolean>;
  /** Whether to show duty members on the timeline - default: true */
  showDutyMembers: Ref<boolean>;
  /** Whether to show tenant section headers in the timeline - default: false */
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

const DEFAULT_DAY_WIDTH = 24;
const MIN_DAY_WIDTH = 4;
const MAX_DAY_WIDTH = 96;

const DEFAULT_LABEL_WIDTH = 220;
const MIN_LABEL_WIDTH = 100;
const MAX_LABEL_WIDTH = 400;

function loadStoredSettings(): Partial<StoredSettings> {
  if (typeof window === 'undefined') return {};
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored ? JSON.parse(stored) : {};
  } catch {
    return {};
  }
}

function saveStoredSettings(settings: StoredSettings) {
  if (typeof window === 'undefined') return;
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
  } catch {
    // Ignore storage errors
  }
}

/**
 * Creates and provides Gantt settings to child components.
 * Call this once in the parent component (e.g., ShowAtstovavimas.vue).
 */
export function provideGanttSettings(): GanttSettings {
  const stored = loadStoredSettings();

  const dayWidthPx = ref<number>(stored.dayWidthPx ?? DEFAULT_DAY_WIDTH);
  const labelWidth = ref<number>(stored.labelWidth ?? DEFAULT_LABEL_WIDTH);
  const detailsExpanded = ref<boolean>(stored.detailsExpanded ?? false);
  const showDutyMembers = ref<boolean>(stored.showDutyMembers ?? true);
  const showTenantHeaders = ref<boolean>(stored.showTenantHeaders ?? false);
  const centerDateTimestamp = ref<number | null>(stored.centerDateTimestamp ?? null);
  const verticalScrollPosition = ref<number | null>(stored.verticalScrollPosition ?? null);

  // Persist settings on change
  function persistSettings() {
    saveStoredSettings({
      dayWidthPx: dayWidthPx.value,
      labelWidth: labelWidth.value,
      detailsExpanded: detailsExpanded.value,
      showDutyMembers: showDutyMembers.value,
      showTenantHeaders: showTenantHeaders.value,
      centerDateTimestamp: centerDateTimestamp.value,
      verticalScrollPosition: verticalScrollPosition.value,
    });
  }

  watch([dayWidthPx, labelWidth, detailsExpanded, showDutyMembers, showTenantHeaders, centerDateTimestamp, verticalScrollPosition], () => {
    persistSettings();
  });

  function setDayWidth(width: number) {
    dayWidthPx.value = Math.max(MIN_DAY_WIDTH, Math.min(MAX_DAY_WIDTH, width));
  }

  function setLabelWidth(width: number) {
    labelWidth.value = Math.max(MIN_LABEL_WIDTH, Math.min(MAX_LABEL_WIDTH, width));
  }

  function toggleDetailsExpanded() {
    detailsExpanded.value = !detailsExpanded.value;
  }

  function setCenterDate(date: Date | null) {
    centerDateTimestamp.value = date ? date.getTime() : null;
  }

  function setVerticalScrollPosition(position: number | null) {
    verticalScrollPosition.value = position;
  }

  function resetSettings() {
    dayWidthPx.value = DEFAULT_DAY_WIDTH;
    labelWidth.value = DEFAULT_LABEL_WIDTH;
    detailsExpanded.value = false;
    showDutyMembers.value = true;
    showTenantHeaders.value = false;
    centerDateTimestamp.value = null;
    verticalScrollPosition.value = null;
    if (typeof window !== 'undefined') {
      localStorage.removeItem(STORAGE_KEY);
    }
  }

  const settings: GanttSettings = {
    dayWidthPx,
    labelWidth,
    detailsExpanded,
    showDutyMembers,
    showTenantHeaders,
    centerDateTimestamp,
    verticalScrollPosition,
    setDayWidth,
    setLabelWidth,
    toggleDetailsExpanded,
    setCenterDate,
    setVerticalScrollPosition,
    resetSettings,
  };

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
    // Fallback: create local settings if not provided (useful for standalone usage)
    // This branch also persists to localStorage for consistency
    if (import.meta.env.DEV) {
      console.warn('useGanttSettings: No provider found, creating local settings with persistence');
    }
    const stored = loadStoredSettings();
    const dayWidthPx = ref<number>(stored.dayWidthPx ?? DEFAULT_DAY_WIDTH);
    const labelWidth = ref<number>(stored.labelWidth ?? DEFAULT_LABEL_WIDTH);
    const detailsExpanded = ref<boolean>(stored.detailsExpanded ?? false);
    const showDutyMembers = ref<boolean>(stored.showDutyMembers ?? true);
    const showTenantHeaders = ref<boolean>(stored.showTenantHeaders ?? false);
    const centerDateTimestamp = ref<number | null>(stored.centerDateTimestamp ?? null);
    const verticalScrollPosition = ref<number | null>(stored.verticalScrollPosition ?? null);

    // Persist settings on change (same as provider branch)
    function persistFallbackSettings() {
      saveStoredSettings({
        dayWidthPx: dayWidthPx.value,
        labelWidth: labelWidth.value,
        detailsExpanded: detailsExpanded.value,
        showDutyMembers: showDutyMembers.value,
        showTenantHeaders: showTenantHeaders.value,
        centerDateTimestamp: centerDateTimestamp.value,
        verticalScrollPosition: verticalScrollPosition.value,
      });
    }

    watch([dayWidthPx, labelWidth, detailsExpanded, showDutyMembers, showTenantHeaders, centerDateTimestamp, verticalScrollPosition], () => {
      persistFallbackSettings();
    });

    return {
      dayWidthPx,
      labelWidth,
      detailsExpanded,
      showDutyMembers,
      showTenantHeaders,
      centerDateTimestamp,
      verticalScrollPosition,
      setDayWidth: (w) => { dayWidthPx.value = Math.max(MIN_DAY_WIDTH, Math.min(MAX_DAY_WIDTH, w)); },
      setLabelWidth: (w) => { labelWidth.value = Math.max(MIN_LABEL_WIDTH, Math.min(MAX_LABEL_WIDTH, w)); },
      toggleDetailsExpanded: () => { detailsExpanded.value = !detailsExpanded.value; },
      setCenterDate: (date: Date | null) => { centerDateTimestamp.value = date ? date.getTime() : null; },
      setVerticalScrollPosition: (position: number | null) => { verticalScrollPosition.value = position; },
      resetSettings: () => {
        dayWidthPx.value = DEFAULT_DAY_WIDTH;
        labelWidth.value = DEFAULT_LABEL_WIDTH;
        detailsExpanded.value = false;
        showDutyMembers.value = true;
        showTenantHeaders.value = false;
        centerDateTimestamp.value = null;
        verticalScrollPosition.value = null;
        if (typeof window !== 'undefined') {
          localStorage.removeItem(STORAGE_KEY);
        }
      },
    };
  }

  return settings;
}

// Export the injection key for testing purposes
export { GANTT_SETTINGS_KEY };
