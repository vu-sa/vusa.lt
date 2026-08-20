import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

import type { CadenceDraft, CadenceRow } from './index';

/**
 * The create/update/delete trio, shared by the settings screen and the institution form.
 *
 * Both surfaces post to the same `settings.cadences.*` routes; who may do so is decided
 * by CadencePolicy from the `institution_id` in the payload, not by which page called.
 */
export function useCadenceCrud(institutionId: string | null = null) {
  const editingId = ref<string | null>(null);
  const adding = ref(false);
  const processing = ref(false);

  const visitOptions = {
    preserveScroll: true,
    onStart: () => { processing.value = true; },
    onFinish: () => { processing.value = false; },
  };

  function reset(): void {
    editingId.value = null;
    adding.value = false;
  }

  function create(value: CadenceDraft, scopeId: string | null = institutionId): void {
    router.post(route('settings.cadences.store'), { ...value, institution_id: scopeId }, {
      ...visitOptions,
      onSuccess: reset,
    });
  }

  function update(cadence: CadenceRow, value: CadenceDraft): void {
    router.patch(route('settings.cadences.update', cadence.id), {
      ...value,
      institution_id: cadence.institution_id,
    }, { ...visitOptions, onSuccess: reset });
  }

  function destroy(cadence: CadenceRow): void {
    router.delete(route('settings.cadences.destroy', cadence.id), visitOptions);
  }

  return { editingId, adding, processing, reset, create, update, destroy };
}

/**
 * What a new row should start as: one year on from the newest cadence in this scope,
 * falling back to the configured month-days when there is nothing to extrapolate from.
 */
export function prefillFrom(
  cadences: CadenceRow[],
  defaults: { default_start_month_day: string; default_end_month_day: string },
): CadenceDraft {
  const latest = [...cadences].sort((a, b) => a.start_date.localeCompare(b.start_date)).at(-1);

  if (latest) {
    return { start_date: addYear(latest.start_date), end_date: addYear(latest.end_date) };
  }

  const year = new Date().getFullYear();
  const start = `${year}-${defaults.default_start_month_day}`;
  const sameYearEnd = `${year}-${defaults.default_end_month_day}`;

  // A term ending on or before it starts runs into the following year (07-01 → 06-30);
  // one that does not is a same-year term. Mirrors CadenceSettings::windowFor().
  return {
    start_date: start,
    end_date: sameYearEnd <= start ? `${year + 1}-${defaults.default_end_month_day}` : sameYearEnd,
  };
}

function addYear(date: string): string {
  const [year, ...rest] = date.split('-');

  return [Number(year) + 1, ...rest].join('-');
}
