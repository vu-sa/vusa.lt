<template>
  <div data-slot="dutiable-timeline-editor" class="flex min-h-0 flex-col gap-3">
    <DutiableTimelineToolbar
      :scope="scope"
      :visible-count="visibleRows.length"
      :include-ended="includeEnded"
      :month-width-px="monthWidthPx"
      :timeline-colors="timelineColors"
      :cadence-options="cadenceOptions"
      :tenant-options="tenantOptions"
      :cadence-ids="cadenceFilter"
      :tenant-keys="tenantFilter"
      @update:include-ended="includeEnded = $event"
      @update:month-width-px="monthWidthPx = $event"
      @update:cadence-ids="cadenceFilter = $event"
      @update:tenant-keys="tenantFilter = $event"
    />

    <Alert v-if="meta?.truncated" variant="destructive">
      <AlertDescription class="text-xs">
        {{ $t('dutiables.timeline.truncated', { max: meta.max_rows }) }}
      </AlertDescription>
    </Alert>

    <div v-if="isFetching && rows.length === 0" class="space-y-2">
      <Skeleton v-for="n in 6" :key="n" class="h-6 w-full" />
    </div>

    <EmptyState
      v-else-if="rows.length === 0"
      :title="$t('dutiables.timeline.empty.title')"
      :description="$t('dutiables.timeline.empty.description')"
    />

    <template v-else>
      <EmptyState
        v-if="visibleRows.length === 0"
        :title="$t('dutiables.timeline.filters.empty_title')"
        :description="$t('dutiables.timeline.filters.empty_description')"
      />

      <DutiableGantt
        v-else
        class="min-h-0 flex-auto"
        data-tour="timeline-chart"
        :layout-rows="layoutRows"
        :rows="visibleRows"
        :cadences="cadences"
        :band-cadences="bandCadences"
        :highlighted-cadence-ids="highlightedCadenceIds"
        :domain="domain"
        :total-height="totalHeight"
        :collapsed="collapsed"
        :group-summaries="groupSummaries"
        :all-collapsed="allCollapsed"
        :sort-mode="sortMode"
        :sortable="sortable"
        :month-width-px="monthWidthPx"
        :selected-ids="selectedIds"
        :staged="drawn"
        :diagnostic-severity-by-row="diagnosticSeverityByRow"
        :row-top="rowTop"
        :row-height-for="rowHeightFor"
        :row-index="rowIndex"
        @toggle-group="toggleGroup"
        @toggle-all="setAllCollapsed(!allCollapsed)"
        @update:sort-mode="sortMode = $event"
        @toggle-selection="toggleSelection"
        @toggle-group-selection="toggleGroupSelection"
        @select="onSelect"
        @stage="stageMany"
      />

      <DutiableTimelineDock>
        <template #selection>
          <DutiableTimelineSelectionPanel
            data-tour="timeline-selection"
            :row="activeRow"
            :cadences="cadences"
            :staged="staged"
            :selected-rows="selectedRows"
            @stage="stage"
            @select-source="selectRow"
            @align="onAlign"
            @close="onClose"
            @remove="onRemove"
            @merge="onMerge"
            @set-dates="onSetDates"
          />
        </template>

        <template #suggestions>
          <DutiableTimelineSuggestions
            data-tour="timeline-suggestions"
            :findings="findings"
            :counts="counts"
            :rows="visibleRows"
            :processing="processing"
            @focus="focusRows"
            @apply="onApplySuggestions"
          />
        </template>

        <template #save>
          <DutiableTimelineDirtyBar
            data-tour="timeline-save"
            :dirty-count="dirtyCount"
            :is-dirty="isDirty"
            :processing="processing"
            :sync-pending="pending.size > 0"
            @preview="onPreview"
            @discard="revertAll"
            @save="onSave"
          />
        </template>
      </DutiableTimelineDock>
    </template>

    <DutiableTimelineDiffSheet
      :open="preview.isOpen.value"
      :plan="preview.plan.value"
      :loading="preview.isFetching.value"
      :processing="processing"
      @update:open="preview.isOpen.value = $event"
      @confirm="onConfirmDiff"
    />

    <AccessChangeWarningDialog
      :open="accessGuard.open.value"
      :report="accessGuard.report.value"
      @update:open="accessGuard.open.value = $event"
      @confirm="accessGuard.confirm"
      @cancel="accessGuard.cancel"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, shallowRef, toRef, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';

import AccessChangeWarningDialog from '@/Components/AdminForms/AccessChangeWarningDialog.vue';
import { isDarkModeActive } from '@/Components/Graphs/ganttColors';
import { EmptyState } from '@/Components/Patterns';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Skeleton } from '@/Components/ui/skeleton';
import { useAccessChangeGuard } from '@/Composables/useAccessChangeGuard';

import { bandLadder } from './cadencePools';
import DutiableGantt from './DutiableGantt.vue';
import DutiableTimelineDiffSheet from './DutiableTimelineDiffSheet.vue';
import DutiableTimelineDirtyBar from './DutiableTimelineDirtyBar.vue';
import DutiableTimelineDock from './DutiableTimelineDock.vue';
import DutiableTimelineSelectionPanel from './DutiableTimelineSelectionPanel.vue';
import DutiableTimelineSuggestions from './DutiableTimelineSuggestions.vue';
import DutiableTimelineToolbar from './DutiableTimelineToolbar.vue';
import { getTimelineColors } from './timelineColors';
import type { FilterOption } from './DutiableTimelineFilterMenu.vue';
import { useDutiableDiagnostics } from './composables/useDutiableDiagnostics';
import { useDutiableLayout, type RowSortMode } from './composables/useDutiableLayout';
import { useDutiableStaging } from './composables/useDutiableStaging';
import { useDutiableTimelineData } from './composables/useDutiableTimelineData';
import { useDutiableTimelinePreview } from './composables/useDutiableTimelinePreview';
import {
  DEFAULT_MONTH_WIDTH, MAX_MONTH_WIDTH, MIN_MONTH_WIDTH, VIEW_STORAGE_KEY,
} from './constants';
import type { ParsedRow, StagedDates, TimelineOperation, TimelineScopeType } from './types';

const props = defineProps<{
  scopeType: TimelineScopeType;
  scopeId: string;
}>();

/**
 * Zoom and "show ended" survive a reload, the way the meetings chart's do — re-picking
 * them on every visit was the single most repeated action in the editor.
 *
 * Clamped on read: a value persisted before MAX_MONTH_WIDTH came down would otherwise
 * restore a zoom level the slider can no longer express.
 */
const view = useStorage(VIEW_STORAGE_KEY, {
  monthWidthPx: DEFAULT_MONTH_WIDTH,
  includeEnded: true,
});

const monthWidthPx = computed({
  get: () => Math.min(MAX_MONTH_WIDTH, Math.max(MIN_MONTH_WIDTH, view.value.monthWidthPx)),
  set: (value: number) => { view.value = { ...view.value, monthWidthPx: value }; },
});

const includeEnded = computed({
  get: () => view.value.includeEnded,
  set: (value: boolean) => { view.value = { ...view.value, includeEnded: value }; },
});

const selectedIds = ref(new Set<string>());
const activeId = ref<string | null>(null);
const processing = ref(false);
const cadenceFilter = ref<string[]>([]);
const tenantFilter = ref<string[]>([]);

const timelineColors = shallowRef(getTimelineColors(isDarkModeActive()));

const { rows, groups, cadences, scope, meta, domain, diagnostics, isFetching, execute } = useDutiableTimelineData(
  toRef(props, 'scopeType'),
  toRef(props, 'scopeId'),
  { includeEnded },
);

/** Rows with no tenant share this key, so "no tenant" is a filterable value like any other. */
const NO_TENANT = '__none__';

/** Same trick for a row whose period touches no term at all. */
const NO_CADENCE = '__none__';

const cadenceLabelById = computed(() => new Map(cadences.value.map(cadence => [cadence.id, cadence.label])));

/** A re-elected member belongs to every term they served in, so one row feeds several keys. */
function cadenceKeysOf(row: ParsedRow): string[] {
  return row.cadence_ids.length > 0 ? row.cadence_ids : [NO_CADENCE];
}

const cadenceOptions = computed<FilterOption[]>(() => buildOptions(
  rows.value,
  cadenceKeysOf,
  value => cadenceLabelById.value.get(value) ?? $t('dutiables.timeline.filters.no_cadence'),
));

/**
 * Only assignments carrying an explicit cross-tenant tenant. Everywhere else the column is
 * null for every row, and a filter whose only value is "no unit" narrows nothing.
 */
const tenantOptions = computed<FilterOption[]>(() => buildOptions(
  rows.value.filter(row => row.tenant_id !== null),
  row => String(row.tenant_id),
  value => rows.value.find(row => String(row.tenant_id) === value)?.tenant_shortname ?? value,
));

function buildOptions(
  source: ParsedRow[],
  keysOf: (row: ParsedRow) => string | string[],
  labelOf: (key: string) => string,
): FilterOption[] {
  const counts = new Map<string, number>();

  for (const row of source) {
    const keys = keysOf(row);

    for (const key of Array.isArray(keys) ? keys : [keys]) {
      counts.set(key, (counts.get(key) ?? 0) + 1);
    }
  }

  return [...counts.entries()]
    .map(([value, count]) => ({ value, label: labelOf(value), count }))
    .sort((a, b) => a.label.localeCompare(b.label));
}

/**
 * Filtering is client-side: the payload already carries the whole scope, so narrowing it
 * server-side would cost a round trip and lose the staged edits on the way.
 *
 * A cadence matches on membership, not equality — filtering to 2024–2025 must keep a seat
 * held from 2023 through 2026, which is the ordinary shape of a re-election.
 */
const visibleRows = computed(() => rows.value.filter((row) => {
  const tenantKey = row.tenant_id === null ? NO_TENANT : String(row.tenant_id);

  return (cadenceFilter.value.length === 0
    || cadenceKeysOf(row).some(key => cadenceFilter.value.includes(key)))
    && (tenantFilter.value.length === 0 || tenantFilter.value.includes(tenantKey));
}));

/**
 * The bands are one ladder, never two: the payload carries the global ladder *and* every
 * override so per-row matching works, but stacking both over the same domain paints the
 * whole chart flat green. Everything else here still gets the full list and narrows per row.
 */
const bandCadences = computed(() => bandLadder(cadences.value, scope.value, visibleRows.value));

/** Terms drawn as selected, so the chart says what the filter did. */
const highlightedCadenceIds = computed(() => new Set(cadenceFilter.value));

/** A group with nothing left to show is a header over empty space. */
const visibleGroups = computed(() => {
  const present = new Set(visibleRows.value.map(row => row.group_key));

  return groups.value.filter(group => present.has(group.key));
});

/** Offered only where a programme is actually recorded — see the chart's controls strip. */
const sortMode = ref<RowSortMode>('default');
const sortable = computed(() => visibleRows.value.some(row => Boolean(row.extras?.study_program)));

const {
  collapsed, layoutRows, totalHeight, groupSummaries, rowTop, rowHeightFor, rowIndex,
  toggleGroup, setAllCollapsed,
} = useDutiableLayout(visibleGroups, visibleRows, sortMode);

// A programme sort that nothing can be sorted by would silently persist across a filter
// change and then reorder rows the moment one reappears.
watch(sortable, (canSort) => {
  if (!canSort) sortMode.value = 'default';
});

const {
  staged, drawn, pending, stage, stageMany, revertAll, settle, dirtyCount, isDirty, operations,
} = useDutiableStaging(rows);

const { findings, counts } = useDutiableDiagnostics(visibleRows, cadences, drawn, diagnostics);

/** Worst severity per row, so the chart's gutter tick never contradicts the panel. */
const diagnosticSeverityByRow = computed(() => {
  const rank = { info: 0, warning: 1, error: 2 } as const;
  const map = new Map<string, 'error' | 'warning' | 'info'>();

  for (const finding of findings.value) {
    for (const rowId of finding.row_ids) {
      const current = map.get(rowId);

      if (current === undefined || rank[finding.severity] > rank[current]) {
        map.set(rowId, finding.severity);
      }
    }
  }

  return map;
});

const preview = useDutiableTimelinePreview();
const accessGuard = useAccessChangeGuard();

const allCollapsed = computed(
  () => visibleGroups.value.length > 0 && collapsed.value.size === visibleGroups.value.length,
);

const activeRow = computed<ParsedRow | null>(
  () => rows.value.find(row => row.id === activeId.value) ?? null,
);

const selectedRows = computed(() => rows.value.filter(row => selectedIds.value.has(row.id)));

/** Ctrl/Cmd extends the selection; a plain click replaces it, and re-clicking clears it. */
function onSelect(row: ParsedRow, event: MouseEvent): void {
  if (event.ctrlKey || event.metaKey) {
    const next = new Set(selectedIds.value);
    next.has(row.id) ? next.delete(row.id) : next.add(row.id);
    selectedIds.value = next;
    activeId.value = row.id;

    return;
  }

  if (activeId.value === row.id && selectedIds.value.size === 1) {
    selectedIds.value = new Set();
    activeId.value = null;

    return;
  }

  selectedIds.value = new Set([row.id]);
  activeId.value = row.id;
}

/** The checkbox path. Ctrl/Cmd-click does the same thing for people who know about it. */
function toggleSelection(rowId: string): void {
  const next = new Set(selectedIds.value);
  next.has(rowId) ? next.delete(rowId) : next.add(rowId);
  selectedIds.value = next;

  if (activeId.value === rowId && !next.has(rowId)) activeId.value = null;
  else if (next.has(rowId)) activeId.value = rowId;
}

/** All-or-nothing: a partly selected group fills up, a full one empties. */
function toggleGroupSelection(key: string): void {
  const ids = visibleRows.value.filter(row => row.group_key === key).map(row => row.id);
  const next = new Set(selectedIds.value);

  if (ids.every(id => next.has(id))) {
    for (const id of ids) next.delete(id);
  } else {
    for (const id of ids) next.add(id);
  }

  selectedIds.value = next;
  activeId.value = [...next][0] ?? null;
}

function selectRow(rowId: string): void {
  selectedIds.value = new Set([rowId]);
  activeId.value = rowId;
}

function focusRows(rowIds: string[]): void {
  selectedIds.value = new Set(rowIds);
  activeId.value = rowIds[0] ?? null;
}

/** Whatever is selected and actually writable — the dock's buttons act on all of it. */
const editableSelection = computed(
  () => [...selectedIds.value].filter(id => rows.value.find(row => row.id === id)?.editable),
);

/**
 * A proposal, never a write: every button here goes through the same dry run the
 * suggestion list does, so nothing is saved without the before → after on screen first.
 */
const pendingOperations = ref<TimelineOperation[]>([]);

function propose(operations: TimelineOperation[]): void {
  // An operation with no rows is not a no-op the planner ignores, it is a pointless round
  // trip — this is the "nothing in the selection is editable" case.
  const targeted = operations.filter(operation => operation.row_ids.length > 0);

  if (targeted.length === 0) return;

  pendingOperations.value = targeted;
  void preview.open(targeted);
}

// No cadence id: the planner aligns each edge to its own term, which is the only right
// answer for a row spanning two of them — and removes the dropdown nobody could read.
function onAlign(): void {
  propose([{ type: 'align_to_cadence', row_ids: editableSelection.value, edges: 'both' }]);
}

function onClose(endDate: string): void {
  propose([{ type: 'close_open_ended', row_ids: editableSelection.value, end_date: endDate }]);
}

function onApplySuggestions(operations: TimelineOperation[]): void {
  propose(operations);
}

/**
 * The multi-row date form. Emitted as one `set_dates` over the whole selection; a null
 * edge means "leave this one alone", which is why the operation is built conditionally.
 */
function onSetDates(dates: { start_date: string | null; end_date: string | null }): void {
  const operation: TimelineOperation = { type: 'set_dates', row_ids: editableSelection.value };

  if (dates.start_date !== null) operation.start_date = dates.start_date;
  if (dates.end_date !== null) operation.end_date = dates.end_date;

  propose([operation]);
}

/**
 * Merging deletes rows, so it is its own endpoint rather than an operation — nothing in
 * the planner's vocabulary can express "and then there was one".
 */
function onMerge(rowIds: string[]): void {
  processing.value = true;

  accessGuard.guardedSubmit(acknowledge => router.post(route('dutiables.timeline.merge'), {
    row_ids: rowIds,
    acknowledge_access_change: acknowledge,
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      selectedIds.value = new Set();
      activeId.value = null;
      void execute();
    },
    onFinish: () => { processing.value = false; },
  }));
}

function onPreview(): void {
  pendingOperations.value = [];
  void preview.open(operations.value);
}

function onConfirmDiff(): void {
  preview.close();
  submit(pendingOperations.value.length > 0 ? pendingOperations.value : operations.value);
}

function onSave(): void {
  submit(operations.value);
}

function submit(payload: TimelineOperation[]): void {
  if (payload.length === 0) return;

  processing.value = true;

  accessGuard.guardedSubmit(acknowledge => router.post(route('dutiables.timeline.apply'), {
    operations: payload,
    acknowledge_access_change: acknowledge,
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Not revertAll(): the ex-officio rows that follow are still queued, so they stay
      // drawn at their projected dates until a refetch shows the database agreeing.
      settle();
      pendingOperations.value = [];
      void execute();
    },
    onFinish: () => { processing.value = false; },
  }));
}

/**
 * `stay` keeps the delete on this page — without it DutiableController redirects to the
 * holder's edit form, which is right for the dutiable editor and wrong for a chart.
 */
function onRemove(rowId: string): void {
  processing.value = true;

  accessGuard.guardedSubmit(acknowledge => router.delete(route('dutiables.destroy', rowId), {
    data: { stay: true, acknowledge_access_change: acknowledge },
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      selectedIds.value = new Set();
      activeId.value = null;
      void execute();
    },
    onFinish: () => { processing.value = false; },
  }));
}

// The palette is read once per render, so a theme flip needs an explicit nudge here too.
let themeObserver: MutationObserver | null = null;

onMounted(() => {
  themeObserver = new MutationObserver(() => {
    timelineColors.value = getTimelineColors(isDarkModeActive());
  });
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-theme'] });
});

onUnmounted(() => themeObserver?.disconnect());

// A filter that hides the selected row would leave the dock describing something the
// user can no longer see.
watch(visibleRows, (next) => {
  const visible = new Set(next.map(row => row.id));

  if (activeId.value !== null && !visible.has(activeId.value)) activeId.value = null;

  selectedIds.value = new Set([...selectedIds.value].filter(id => visible.has(id)));
});

defineExpose({ refresh: execute, stage: (rowId: string, dates: StagedDates) => stage(rowId, dates) });
</script>
