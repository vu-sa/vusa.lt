import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';

import { useFacetSearch, type FacetOption } from './facets';

interface Option {
  value: string | number;
  label: string;
  count?: number | null;
}

export function useFacetOptions<T extends Option>(field: () => string | undefined, options: () => T[], selected: () => Array<string | number>) {
  const search = useFacetSearch();
  const term = ref('');
  const remote = ref<FacetOption[] | null>(null);
  const loading = ref(false);
  const failed = ref(false);
  let controller: AbortController | undefined;
  let generation = 0;

  const run = useDebounceFn(async (query: string, request: number) => {
    const name = field();
    if (!search || !name || request !== generation) return;
    controller = new AbortController();
    try {
      const values = await search(name, query, controller.signal);
      if (request === generation) remote.value = values;
    }
    catch (error) {
      if (request === generation && !(error instanceof Error && error.name === 'AbortError')) failed.value = true;
    }
    finally {
      if (request === generation) loading.value = false;
    }
  }, 300);

  const update = (value: string) => {
    controller?.abort();
    generation++;
    remote.value = null;
    failed.value = false;
    loading.value = Boolean(value.trim() && search && field());
    if (value.trim()) void run(value.trim(), generation);
  };
  watch(term, update);
  watch([field, options, selected], () => {
    if (term.value.trim()) update(term.value);
  }, { deep: true });
  onBeforeUnmount(() => {
    generation++;
    controller?.abort();
  });

  const values = computed(() => {
    const original = options();
    const sticky = original.filter(option => selected().map(String).includes(String(option.value)));
    if (!term.value.trim()) return original;
    const matches: T[] = remote.value
      ? remote.value.map((option) => {
          const existing = original.find(value => String(value.value) === option.value);
          return { ...existing, ...option, label: existing?.label ?? option.value } as T;
        })
      : original.filter(option => option.label.toLocaleLowerCase().includes(term.value.trim().toLocaleLowerCase()));
    return [...sticky, ...matches.filter(option => !sticky.some(value => String(value.value) === String(option.value)))];
  });

  return { term, values, loading, failed };
}
