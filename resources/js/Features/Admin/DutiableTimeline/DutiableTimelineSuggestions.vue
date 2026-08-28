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
      <p class="text-[10px] text-muted-foreground">
        {{ $t('dutiables.timeline.diagnostics.advisory') }}
      </p>
      <ul class="min-h-0 flex-1 space-y-1 overflow-y-auto pr-1">
        <template v-for="section in sections" :key="section.key">
          <!--
            A whole class of finding folded into one line. `spans_cadences` fires on every
            re-elected member, which is the ordinary shape of a long-serving seat rather
            than a fault, and thirty of them buried the handful that matter.
          -->
          <li v-if="section.folded">
            <button
              type="button"
              class="flex w-full items-center gap-2 rounded-md px-1.5 py-1 text-left hover:bg-accent/50"
              @click="toggleSection(section.code)"
            >
              <ChevronRight
                class="size-3 shrink-0 text-muted-foreground transition-transform"
                :class="{ 'rotate-90': expanded.has(section.code) }"
              />
              <span class="min-w-0 flex-1 truncate text-[11px] font-medium" :class="severityClass(section.severity)">
                {{ $t(`dutiables.timeline.diagnostics.codes.${section.code}`) }}
              </span>
              <span class="shrink-0 text-[10px] tabular-nums text-muted-foreground">
                {{ section.entries.length }}
              </span>
            </button>
          </li>

          <li
            v-for="entry in section.folded && !expanded.has(section.code) ? [] : section.entries"
            :key="entry.key"
            class="flex items-start gap-2 rounded-md px-1.5 py-1 hover:bg-accent/50"
            :class="{ 'pl-5': section.folded }"
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
                <span
                  v-if="!section.folded"
                  class="text-[11px] font-medium"
                  :class="severityClass(entry.finding.severity)"
                >
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
        </template>
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
import { ChevronRight, Wrench } from 'lucide-vue-next';

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

/**
 * Codes that are noise by default rather than findings: true of the whole set often enough
 * that listing each one drowns the rest. They are still there, one click away.
 */
const FOLDED_CODES = new Set(['spans_cadences']);

interface SuggestionSection {
  /** Unique per rendered section; several findings can share one code. */
  key: string;
  code: string;
  severity: TimelineDiagnostic['severity'];
  folded: boolean;
  entries: SuggestionEntry[];
}

/**
 * Folded codes are gathered into one section each; everything else keeps its own line, in
 * the severity order the planner needs.
 */
const sections = computed<SuggestionSection[]>(() => {
  const result: SuggestionSection[] = [];
  const foldedByCode = new Map<string, SuggestionSection>();

  for (const entry of entries.value) {
    const code = entry.finding.code;

    if (!FOLDED_CODES.has(code)) {
      result.push({
        key: entry.key, code, severity: entry.finding.severity, folded: false, entries: [entry],
      });

      continue;
    }

    const section = foldedByCode.get(code);

    if (section) {
      section.entries.push(entry);

      continue;
    }

    const created: SuggestionSection = {
      key: code, code, severity: entry.finding.severity, folded: true, entries: [entry],
    };
    foldedByCode.set(code, created);
    result.push(created);
  }

  return result;
});

const expanded = ref(new Set<string>());

function toggleSection(code: string): void {
  const next = new Set(expanded.value);
  next.has(code) ? next.delete(code) : next.add(code);
  expanded.value = next;
}

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
