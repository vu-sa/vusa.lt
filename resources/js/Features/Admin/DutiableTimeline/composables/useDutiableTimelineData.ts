import { computed, ref, watch, type Ref } from 'vue';

import { useApi } from '@/Composables/useApi';

import type { ParsedCadence, ParsedRow, TimelinePayload, TimelineScopeType } from '../types';

/** Dates arrive as `YYYY-MM-DD`; parse at noon so a timezone shift cannot roll the day. */
export function parseTimelineDate(value: string): Date {
  const [year, month, day] = value.split('-').map(Number);

  return new Date(year, month - 1, day, 12, 0, 0);
}

export function toDateString(date: Date): string {
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${date.getFullYear()}-${month}-${day}`;
}

export function useDutiableTimelineData(
  scopeType: Ref<TimelineScopeType>,
  scopeId: Ref<string>,
  options: { includeEnded?: Ref<boolean>; immediate?: boolean } = {},
) {
  const includeEnded = options.includeEnded ?? ref(true);

  const url = computed(() => route('api.v1.admin.dutiableTimeline.index', {
    scope: scopeType.value,
    scope_id: scopeId.value,
    include_ended: includeEnded.value ? 1 : 0,
  }));

  const { data, error, isFetching, execute } = useApi<TimelinePayload>(url, {
    immediate: options.immediate ?? true,
    refetch: true,
  });

  const rows = computed<ParsedRow[]>(() => (data.value?.rows ?? []).map(row => ({
    ...row,
    // Defaulted here rather than at every read: an older cached payload predates the field.
    cadence_ids: row.cadence_ids ?? [],
    startDate: parseTimelineDate(row.start_date),
    endDate: row.end_date ? parseTimelineDate(row.end_date) : null,
  })));

  const cadences = computed<ParsedCadence[]>(() => (data.value?.cadences ?? []).map(cadence => ({
    ...cadence,
    startDate: parseTimelineDate(cadence.start_date),
    endDate: parseTimelineDate(cadence.end_date),
  })));

  const groups = computed(() => data.value?.groups ?? []);
  const diagnostics = computed(() => data.value?.diagnostics ?? []);
  const scope = computed(() => data.value?.scope ?? null);
  const meta = computed(() => data.value?.meta ?? null);

  /**
   * The drawn window: the data's own span padded to whole cadences where they exist,
   * so a bar never starts flush against the left edge with no context.
   */
  const domain = computed<[Date, Date]>(() => {
    const dates: Date[] = [];

    for (const row of rows.value) {
      dates.push(row.startDate);
      dates.push(row.endDate ?? new Date());
    }

    for (const cadence of cadences.value) {
      dates.push(cadence.startDate, cadence.endDate);
    }

    if (dates.length === 0) {
      const now = new Date();

      return [new Date(now.getFullYear() - 1, 0, 1), new Date(now.getFullYear() + 1, 11, 31)];
    }

    const min = new Date(Math.min(...dates.map(d => d.getTime())));
    const max = new Date(Math.max(...dates.map(d => d.getTime())));

    // Whole months on both ends keeps the month grid from starting mid-column.
    return [
      new Date(min.getFullYear(), min.getMonth() - 1, 1),
      new Date(max.getFullYear(), max.getMonth() + 2, 0),
    ];
  });

  // A scope change invalidates everything downstream; refetch rather than showing
  // the previous scope's bars under the new header.
  watch([scopeType, scopeId], () => void execute());

  return { data, rows, groups, cadences, scope, meta, domain, diagnostics, error, isFetching, execute };
}
