<template>
  <section data-slot="dutiable-timeline-selection" class="flex h-full min-h-0 flex-col gap-2 overflow-y-auto p-3">
    <p class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
      {{ $t('dutiables.timeline.dock.selection') }}
      <span v-if="selectedCount > 1">({{ selectedCount }})</span>
    </p>

    <p v-if="!row" class="py-4 text-center text-xs text-muted-foreground">
      {{ $t('dutiables.timeline.inspector.empty') }}
    </p>

    <!--
      Several rows selected: the panel stops describing one of them and becomes a bulk
      form. Dates are typed rather than dragged — a drag has no meaning across rows that
      start in different years.
    -->
    <template v-else-if="selectedRows.length > 1">
      <p class="text-xs text-muted-foreground">
        {{ $t('dutiables.timeline.dock.multi_hint', { editable: editableCount, total: selectedRows.length }) }}
      </p>

      <div class="grid gap-2 sm:grid-cols-2">
        <div class="space-y-1">
          <Label for="bulk-start" class="text-[11px] text-muted-foreground">
            {{ $t('dutiables.timeline.inspector.start_date') }}
          </Label>
          <TimelineDateField
            id="bulk-start"
            :label="$t('dutiables.timeline.inspector.start_date')"
            :model-value="bulkStart"
            :disabled="editableCount === 0"
            @update:model-value="bulkStart = $event"
          />
        </div>

        <div class="space-y-1">
          <Label for="bulk-end" class="text-[11px] text-muted-foreground">
            {{ $t('dutiables.timeline.inspector.end_date') }}
          </Label>
          <TimelineDateField
            id="bulk-end"
            :label="$t('dutiables.timeline.inspector.end_date')"
            :model-value="bulkEnd"
            :disabled="editableCount === 0"
            @update:model-value="bulkEnd = $event"
          />
        </div>
      </div>

      <div class="mt-auto flex flex-wrap items-center gap-1.5 pt-1">
        <Button
          size="xs"
          :disabled="editableCount === 0 || (bulkStart === null && bulkEnd === null)"
          @click="runBulkDates"
        >
          {{ $t('dutiables.timeline.actions.apply_dates', { count: editableCount }) }}
        </Button>

        <Button v-if="editableCount > 0" size="xs" variant="outline" @click="emit('align')">
          <CalendarCheck class="size-3.5" />
          {{ $t('dutiables.timeline.actions.align') }}
        </Button>

        <AlertDialog v-if="canMerge">
          <AlertDialogTrigger as-child>
            <Button size="xs" variant="outline">
              <Merge class="size-3.5" />
              {{ $t('dutiables.timeline.actions.merge') }}
            </Button>
          </AlertDialogTrigger>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>{{ $t('dutiables.timeline.actions.merge_title') }}</AlertDialogTitle>
              <AlertDialogDescription>
                {{ $t('dutiables.timeline.actions.merge_description', {
                  count: selectedRows.length,
                  holder: selectedRows[0].holder_name ?? '—',
                  duty: selectedRows[0].duty_name ?? '—',
                  start: mergedSpan.start,
                  end: mergedSpan.end,
                }) }}
                <span v-if="mergeLosesExtras" class="mt-2 block text-destructive">
                  {{ $t('dutiables.timeline.actions.merge_extras_warning') }}
                </span>
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel>{{ $t('dutiables.timeline.actions.remove_cancel') }}</AlertDialogCancel>
              <AlertDialogAction @click="emit('merge', selectedRows.map(entry => entry.id))">
                {{ $t('dutiables.timeline.actions.merge_confirm') }}
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>

        <p v-else class="text-[10px] text-muted-foreground">
          {{ $t('dutiables.timeline.actions.merge_hint') }}
        </p>
      </div>
    </template>

    <template v-else>
      <div class="space-y-0.5">
        <div class="flex items-center gap-1.5">
          <Link :href="route('users.show', row.holder_id)" class="truncate text-sm font-semibold hover:underline">
            {{ row.holder_name }}
          </Link>
          <Badge v-if="row.tenant_shortname" variant="secondary" class="shrink-0 text-[10px]">
            {{ row.tenant_shortname }}
          </Badge>
          <Badge v-if="row.is_derived" variant="outline" class="shrink-0 text-[10px]">
            {{ $t('dutiables.timeline.inspector.ex_officio') }}
          </Badge>
          <DutiableExtrasBadge v-if="row.extras" :extras="row.extras" />
        </div>
        <Link :href="route('duties.show', row.duty_id)" class="block truncate text-xs text-muted-foreground hover:underline">
          {{ row.duty_name }}
        </Link>
      </div>

      <Alert v-if="row.is_derived" class="py-2">
        <AlertDescription class="space-y-1.5 text-[11px]">
          <p>{{ $t('dutiables.timeline.inspector.ex_officio_managed', { duty: row.source?.duty_name ?? '—' }) }}</p>
          <Button v-if="row.source" size="xs" variant="outline" @click="emit('select-source', row.source.id)">
            {{ $t('dutiables.timeline.inspector.select_source') }}
          </Button>
        </AlertDescription>
      </Alert>

      <Alert v-else-if="!row.editable" variant="destructive" class="py-2">
        <AlertDescription class="text-[11px]">
          {{ $t('dutiables.timeline.inspector.not_editable') }}
        </AlertDescription>
      </Alert>

      <div class="grid gap-2 sm:grid-cols-2">
        <div class="space-y-1">
          <Label for="selection-start" class="text-[11px] text-muted-foreground">
            {{ $t('dutiables.timeline.inspector.start_date') }}
          </Label>
          <TimelineDateField
            id="selection-start"
            :label="$t('dutiables.timeline.inspector.start_date')"
            :model-value="dates.start_date"
            :disabled="!canEdit"
            @update:model-value="onStart"
          />
        </div>

        <div class="space-y-1">
          <Label for="selection-end" class="text-[11px] text-muted-foreground">
            {{ $t('dutiables.timeline.inspector.end_date') }}
          </Label>
          <TimelineDateField
            id="selection-end"
            :label="$t('dutiables.timeline.inspector.end_date')"
            :model-value="dates.end_date"
            :disabled="!canEdit"
            @update:model-value="onEnd"
          />
          <label class="flex items-center gap-2 text-[11px] text-muted-foreground">
            <Switch :model-value="openEnded" :disabled="!canEdit" @update:model-value="onOpenEnded" />
            {{ $t('dutiables.timeline.inspector.open_ended_toggle') }}
          </label>
        </div>
      </div>

      <!-- Spelled out rather than left to the tooltip: on the selected row these are the
           details a merge or a delete would silently take with it. -->
      <dl v-if="row.extras" class="space-y-0.5 rounded-md bg-muted/40 px-2 py-1.5">
        <div v-if="row.extras.email" class="flex gap-1.5 text-[11px]">
          <dt class="shrink-0 text-muted-foreground">
            {{ $t('dutiables.timeline.extras.email') }}:
          </dt>
          <dd class="min-w-0 truncate">
            {{ row.extras.email }}
          </dd>
        </div>
        <div v-if="row.extras.study_program" class="flex gap-1.5 text-[11px]">
          <dt class="shrink-0 text-muted-foreground">
            {{ $t('dutiables.timeline.extras.study_program') }}:
          </dt>
          <dd class="min-w-0 truncate">
            {{ row.extras.study_program }}
          </dd>
        </div>
        <div v-if="row.extras.description" class="flex gap-1.5 text-[11px]">
          <dt class="shrink-0 text-muted-foreground">
            {{ $t('dutiables.timeline.extras.description') }}:
          </dt>
          <dd class="min-w-0 truncate">
            {{ row.extras.description }}
          </dd>
        </div>
        <div v-if="row.extras.photo || row.extras.original_duty_name" class="flex gap-1.5 text-[11px] text-muted-foreground">
          <span v-if="row.extras.photo">{{ $t('dutiables.timeline.extras.photo_set') }}</span>
          <span v-if="row.extras.original_duty_name">{{ $t('dutiables.timeline.extras.original_duty_name_set') }}</span>
        </div>
      </dl>

      <p class="text-[11px]">
        <span class="text-muted-foreground">{{ $t('dutiables.timeline.duration.label') }}:</span>
        <span class="ml-1 tabular-nums">{{ duration }}</span>
      </p>

      <p v-if="cadence" class="text-[11px]">
        <span class="text-muted-foreground">{{ cadence.label }}</span>
        <span
          class="ml-1"
          :class="driftDays === 0 ? 'text-muted-foreground' : 'text-amber-600 dark:text-amber-400'"
        >
          {{ driftDays === 0
            ? $t('dutiables.timeline.inspector.aligned')
            : $t('dutiables.timeline.inspector.off_by', { days: Math.abs(driftDays ?? 0) }) }}
        </span>
      </p>

      <div class="mt-auto flex flex-wrap items-center gap-1.5 pt-1">
        <Button v-if="canEdit" size="xs" variant="outline" @click="emit('align')">
          <CalendarCheck class="size-3.5" />
          {{ $t('dutiables.timeline.actions.align') }}
        </Button>

        <Popover v-if="canEdit" v-model:open="closeOpen">
          <PopoverTrigger as-child>
            <Button size="xs" variant="outline">
              <CalendarX class="size-3.5" />
              {{ $t('dutiables.timeline.actions.close') }}
            </Button>
          </PopoverTrigger>
          <PopoverContent align="start" class="w-60 space-y-2 p-3">
            <Label class="text-[11px] text-muted-foreground">
              {{ $t('dutiables.timeline.actions.close_end_date') }}
            </Label>
            <TimelineDateField
              :label="$t('dutiables.timeline.actions.close_end_date')"
              :model-value="closeEndDate"
              @update:model-value="closeEndDate = $event"
            />
            <Button size="xs" variant="ghost" class="w-full" @click="closeEndDate = yesterday">
              {{ $t('dutiables.timeline.actions.close_yesterday', { date: yesterday }) }}
            </Button>
            <p class="text-[10px] text-muted-foreground">
              {{ $t('dutiables.timeline.actions.close_hint') }}
            </p>
            <Button size="xs" class="w-full" :disabled="closeEndDate === null" @click="runClose">
              {{ $t('dutiables.timeline.actions.close_run') }}
            </Button>
          </PopoverContent>
        </Popover>

        <Button as="a" :href="row.edit_url" target="_blank" size="xs" variant="ghost">
          <ExternalLink class="size-3.5" />
        </Button>

        <AlertDialog v-if="canEdit">
          <AlertDialogTrigger as-child>
            <Button size="xs" variant="ghost" class="text-destructive hover:text-destructive">
              <Trash2 class="size-3.5" />
              {{ $t('dutiables.timeline.actions.remove') }}
            </Button>
          </AlertDialogTrigger>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>{{ $t('dutiables.timeline.actions.remove_title') }}</AlertDialogTitle>
              <AlertDialogDescription>
                {{ $t('dutiables.timeline.actions.remove_description', {
                  holder: row.holder_name ?? '—',
                  duty: row.duty_name ?? '—',
                }) }}
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel>{{ $t('dutiables.timeline.actions.remove_cancel') }}</AlertDialogCancel>
              <AlertDialogAction
                class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                @click="emit('remove', row.id)"
              >
                {{ $t('dutiables.timeline.actions.remove_confirm') }}
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
      </div>
    </template>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { CalendarCheck, CalendarX, ExternalLink, Merge, Trash2 } from 'lucide-vue-next';

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Switch } from '@/Components/ui/switch';

import DutiableExtrasBadge from './DutiableExtrasBadge.vue';
import TimelineDateField from './TimelineDateField.vue';
import { formatDuration } from './duration';
import { resolveCadenceFor } from './composables/useDutiableDiagnostics';
import { parseTimelineDate } from './composables/useDutiableTimelineData';
import type { ParsedCadence, ParsedRow, StagedDates } from './types';

const props = defineProps<{
  row: ParsedRow | null;
  cadences: ParsedCadence[];
  staged: Map<string, StagedDates>;
  /** Every selected row, so the panel can act on the whole set rather than the active one. */
  selectedRows: ParsedRow[];
}>();

const emit = defineEmits<{
  stage: [rowId: string, dates: StagedDates];
  'select-source': [rowId: string];
  align: [];
  close: [endDate: string];
  remove: [rowId: string];
  merge: [rowIds: string[]];
  'set-dates': [dates: { start_date: string | null; end_date: string | null }];
}>();

const selectedCount = computed(() => props.selectedRows.length);
const editableCount = computed(() => props.selectedRows.filter(entry => entry.editable).length);

const bulkStart = ref<string | null>(null);
const bulkEnd = ref<string | null>(null);

// A new selection is a new question; carrying the previous answer over would let a stray
// click apply dates the user typed for a different set of rows.
watch(() => props.selectedRows.map(entry => entry.id).join(','), () => {
  bulkStart.value = null;
  bulkEnd.value = null;
});

/**
 * Mergeable only within one holder's stints on one duty under one tenant — the same
 * grouping MergeDutiables::isMergeable() enforces server-side, mirrored here so the
 * button is absent rather than rejected.
 */
const canMerge = computed(() => {
  if (selectedCount.value < 2 || editableCount.value !== selectedCount.value) return false;

  const keys = new Set(props.selectedRows.map(
    entry => [entry.duty_id, entry.holder_id, entry.tenant_id ?? ''].join('|'),
  ));

  return keys.size === 1 && props.selectedRows.every(entry => !entry.is_derived);
});

/**
 * CollapseOverlappingDutiables only backfills a field the survivor is *missing*, so a
 * loser's email or programme is dropped whenever the survivor already has one of its own.
 */
const mergeLosesExtras = computed(() => {
  const withExtras = props.selectedRows.filter(entry => entry.extras !== null);

  return withExtras.length > 1;
});

/** What the merged row would span: earliest start, latest end, open-ended if any is. */
const mergedSpan = computed(() => {
  const starts = props.selectedRows.map(entry => entry.start_date).sort();
  const ends = props.selectedRows.map(entry => entry.end_date);

  return {
    start: starts[0] ?? '—',
    end: ends.includes(null) ? '∞' : [...ends].sort().at(-1) ?? '—',
  };
});

function runBulkDates(): void {
  emit('set-dates', { start_date: bulkStart.value, end_date: bulkEnd.value });
}

const closeOpen = ref(false);
const closeEndDate = ref<string | null>(null);

/**
 * The rest of the system ends a duty at yesterday's date (DutyController's
 * endDateDutiables), so the quick option offers exactly that rather than today.
 */
const yesterday = computed(() => {
  const date = new Date();
  date.setDate(date.getDate() - 1);

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
});

/** A derived row's dates are mirrored from its source and are never editable here. */
const canEdit = computed(() => props.row !== null && props.row.editable);

/** What the row will be saved as: the staged value when one exists, else the stored one. */
const dates = computed<StagedDates>(() => {
  if (!props.row) return { start_date: '', end_date: null };

  return props.staged.get(props.row.id) ?? { start_date: props.row.start_date, end_date: props.row.end_date };
});

const openEnded = computed(() => dates.value.end_date === null);

/**
 * The term to measure this row against, resolved by the same rule the server uses. The
 * fallback matters: a row starting a month and a half before its term is exactly the
 * drift Align exists for, and "no cadence contains it" would hide the reading.
 */
/** Reads off the staged dates, so a drag updates it before anything is saved. */
const duration = computed(() => formatDuration(
  parseTimelineDate(dates.value.start_date),
  dates.value.end_date ? parseTimelineDate(dates.value.end_date) : null,
));

const cadence = computed<ParsedCadence | null>(
  () => (props.row ? resolveCadenceFor(props.cadences, props.row, dates.value.start_date) : null),
);

const driftDays = computed<number | null>(() => {
  if (!props.row || !cadence.value) return null;

  const [year, month, day] = (dates.value.start_date || '1970-01-01').split('-').map(Number);
  const start = new Date(year, month - 1, day, 12, 0, 0);

  return Math.round((start.getTime() - cadence.value.startDate.getTime()) / 86_400_000);
});

function stage(next: Partial<StagedDates>): void {
  if (!props.row || !canEdit.value) return;

  emit('stage', props.row.id, { ...dates.value, ...next });
}

function onStart(value: string | null): void {
  if (value !== null) stage({ start_date: value });
}

function onEnd(value: string | null): void {
  stage({ end_date: value });
}

function onOpenEnded(value: boolean): void {
  if (value) {
    stage({ end_date: null });

    return;
  }

  // Closing an open-ended row needs a concrete date; the cadence end is the one the
  // user is measuring against anyway.
  stage({ end_date: cadence.value?.end_date ?? dates.value.start_date });
}

function runClose(): void {
  if (closeEndDate.value === null) return;

  emit('close', closeEndDate.value);
  closeOpen.value = false;
}

function pad(value: number): string {
  return String(value).padStart(2, '0');
}
</script>
