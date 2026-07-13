/**
 * Resource availability cache
 *
 * The Typesense resource index only carries static capacity, so the reservation
 * picker fetches date-range availability from the API and caches it per resource
 * for the current reservation window. Availability is refetched when the range
 * changes.
 */

import { computed, ref, watch } from 'vue';

import { useApiMutation } from '@/Composables/useApi';

export interface ReservationSummary {
  id: string;
  name: string;
  quantity: number;
  state: string;
  start_time: number | null;
  end_time: number | null;
}

export interface ResourceAvailability {
  id: string;
  capacity: number;
  is_reservable: boolean;
  lowestCapacityAtDateTimeRange: number;
  strictLowestCapacityAtDateTimeRange: number;
  reservations: ReservationSummary[];
  discrepancies: ReservationSummary[];
}

export interface DateTimeRange {
  start: number;
  end: number;
}

export function useResourceAvailability(dateTimeRange: () => DateTimeRange) {
  const availability = ref<Map<string, ResourceAvailability>>(new Map());
  const pending = ref<Set<string>>(new Set());
  const requestIds = ref<string[]>([]);

  const body = computed(() => ({ ids: requestIds.value, ...dateTimeRange() }));

  const { data, execute, isFetching } = useApiMutation<Record<string, ResourceAvailability>, {
    ids: string[];
    start: number;
    end: number;
  }>(
    route('api.v1.admin.resources.availability'),
    'POST',
    body,
    { showErrorToast: false, showSuccessToast: false },
  );

  /** Fetch availability for any ids not already known for the current range. */
  const ensure = async (ids: string[]) => {
    const missing = ids.filter(id => !availability.value.has(id) && !pending.value.has(id));
    if (missing.length === 0) {
      return;
    }
    missing.forEach(id => pending.value.add(id));
    requestIds.value = missing;

    await execute();

    const result = data.value;
    if (result) {
      const next = new Map(availability.value);
      for (const [id, value] of Object.entries(result)) {
        next.set(id, value);
      }
      availability.value = next;
    }
    missing.forEach(id => pending.value.delete(id));
  };

  // A new reservation window invalidates every cached availability.
  watch(
    () => dateTimeRange(),
    () => {
      availability.value = new Map();
      pending.value = new Set();
    },
    { deep: true },
  );

  return { availability, ensure, isFetching };
}
