<template>
  <section data-slot="dutiable-timeline-suggestions" class="flex h-full min-h-0 flex-col gap-2 overflow-hidden p-3">
    <div class="flex items-center justify-between gap-2">
      <p class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
        {{ $t('dutiables.timeline.dock.suggestions') }}
        <span v-if="findings.length > 0">({{ findings.length }})</span>
      </p>
      <span class="flex shrink-0 gap-1">
        <Badge v-if="counts.error > 0" variant="destructive" class="text-[10px]">{{ counts.error }}</Badge>
        <Badge v-if="counts.warning > 0" variant="secondary" class="text-[10px]">{{ counts.warning }}</Badge>
        <Badge v-if="counts.info > 0" variant="outline" class="text-[10px]">{{ counts.info }}</Badge>
      </span>
    </div>

    <p v-if="findings.length === 0" class="py-4 text-center text-xs text-muted-foreground">
      {{ $t('dutiables.timeline.diagnostics.empty') }}
    </p>

    <template v-else>
      <ul class="min-h-0 flex-1 space-y-1 overflow-y-auto pr-1">
        <li
          v-for="entry in entries"
          :key="entry.key"
          class="flex items-start gap-2 rounded-md px-1.5 py-1 hover:bg-accent/50"
        >
          <Checkbox
            v-if="entry.fixable"
            :id="`suggestion-${entry.key}`"
            :model-value="checked.has(entry.key)"
            class="mt-0.5"
            @update:model-value="toggle(entry.key)"
          />
          <!-- Keeps a non-fixable row's text aligned with the checkboxes above it. -->
          <span v-else class="mt-0.5 size-4 shrink-0" />

          <button type="button" class="min-w-0 flex-1 text-left" @click="emit('focus', entry.finding.row_ids)">
            <span class="flex flex-wrap items-baseline gap-x-1.5">
              <span class="text-[11px] font-medium" :class="severityClass(entry.finding.severity)">
                {{ $t(`dutiables.timeline.diagnostics.codes.${entry.finding.code}`) }}
              </span>
              <span v-if="entry.subject" class="truncate text-[10px] text-muted-foreground">
                {{ entry.subject }}
              </span>
            </span>
            <span v-if="entry.detail" class="block font-mono text-[10px] text-muted-foreground">
              {{ entry.detail }}
            </span>
          </button>
        </li>
      </ul>

      <Button size="xs" class="shrink-0" :disabled="checked.size === 0 || processing" @click="apply">
        <Wrench class="size-3.5" />
        {{ $t('dutiables.timeline.diagnostics.apply_selected', { count: checked.size }) }}
      </Button>
    </template>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Wrench } from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';

import { fixOperationFor } from './composables/useDutiableDiagnostics';
import type { ParsedRow, TimelineDiagnostic, TimelineOperation } from './types';

const props = defineProps<{
  findings: TimelineDiagnostic[];
  counts: { error: number; warning: number; info: number };
  rows: ParsedRow[];
  processing?: boolean;
}>();

const emit = defineEmits<{
  focus: [rowIds: string[]];
  apply: [operations: TimelineOperation[]];
}>();

const SEVERITY_RANK = { error: 0, warning: 1, info: 2 } as const;

const rowsById = computed(() => new Map(props.rows.map(row => [row.id, row])));

interface SuggestionEntry {
  key: string;
  finding: TimelineDiagnostic;
  fixable: boolean;
  subject: string | null;
  detail: string | null;
}

/**
 * Sorted by severity so a batch applies in the order the planner folds it: an `inverted`
 * end has to be cleared before an `overlap` fix sets a real one on the same row.
 */
const entries = computed<SuggestionEntry[]>(() => [...props.findings]
  .sort((a, b) => SEVERITY_RANK[a.severity] - SEVERITY_RANK[b.severity])
  .map((finding, index) => ({
    key: `${finding.code}-${finding.row_ids.join('-') || finding.duty_id}-${index}`,
    finding,
    fixable: fixOperationFor(finding) !== null,
    subject: subjectFor(finding),
    detail: detailFor(finding),
  })));

const checked = ref(new Set<string>());

// Errors are pre-checked because they are never a judgement call; warnings and notes are
// left for the admin to opt into. Re-seeded whenever the finding set changes, which it
// does on every drag.
watch(entries, (next) => {
  checked.value = new Set(
    next.filter(entry => entry.fixable && entry.finding.severity === 'error').map(entry => entry.key),
  );
}, { immediate: true });

function toggle(key: string): void {
  const next = new Set(checked.value);
  next.has(key) ? next.delete(key) : next.add(key);
  checked.value = next;
}

function apply(): void {
  const operations = entries.value
    .filter(entry => checked.value.has(entry.key))
    .map(entry => fixOperationFor(entry.finding))
    .filter((operation): operation is TimelineOperation => operation !== null);

  if (operations.length > 0) emit('apply', operations);
}

/** Who the finding is about — a code alone never told you which row to look at. */
function subjectFor(finding: TimelineDiagnostic): string | null {
  const row = finding.row_ids.map(id => rowsById.value.get(id)).find(entry => entry !== undefined);

  if (!row) return null;

  return [row.holder_name, row.duty_name].filter(Boolean).join(' · ') || null;
}

/** What it would actually write. Every detail below already ships in the payload. */
function detailFor(finding: TimelineDiagnostic): string | null {
  const detail = (finding.detail ?? {}) as {
    suggested_start?: string;
    suggested_end?: string;
    drift_days?: Record<string, number>;
    active?: number;
    places_to_occupy?: number;
    count?: number;
  };
  const row = rowsById.value.get(finding.row_ids[0] ?? '');

  switch (finding.code) {
    case 'overlap':
    case 'boundary_shared':
      return detail.suggested_end
        ? $t('dutiables.timeline.diagnostics.detail.end_move', {
            from: row?.end_date ?? '—',
            to: detail.suggested_end,
          })
        : null;

    case 'inverted':
      return $t('dutiables.timeline.diagnostics.detail.clear_end');

    case 'open_ended_stale':
      return detail.suggested_end
        ? $t('dutiables.timeline.diagnostics.detail.close_at', { date: detail.suggested_end })
        : null;

    case 'off_cadence':
      return detail.drift_days
        ? Object.entries(detail.drift_days)
            .map(([edge, days]) => $t(`dutiables.timeline.diagnostics.detail.drift_${edge}`, { days }))
            .join(' · ')
        : null;

    case 'spans_cadences':
      return $t('dutiables.timeline.diagnostics.detail.spans', {
        count: detail.count ?? 0,
        start: detail.suggested_start ?? '—',
        end: detail.suggested_end ?? '—',
      });

    // The "there is less space than places_to_occupy" message that never said how many.
    case 'understaffed':
      return $t('dutiables.timeline.diagnostics.detail.understaffed', {
        active: detail.active ?? 0,
        places: detail.places_to_occupy ?? 0,
      });

    case 'ex_officio_drift':
      return $t('dutiables.timeline.diagnostics.detail.ex_officio_drift');

    case 'orphan_derived_suspect':
      return $t('dutiables.timeline.diagnostics.orphan_note');

    default:
      return null;
  }
}

function severityClass(severity: string): string {
  if (severity === 'error') return 'text-destructive';
  if (severity === 'warning') return 'text-amber-600 dark:text-amber-400';

  return 'text-muted-foreground';
}
</script>
