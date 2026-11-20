import { computed, ref, type Ref } from 'vue';

import type { FileEntry } from './types';

/** Ticked files for bulk actions, with shift-click ranges over the list as shown. */
export function useFileChecks(files: Ref<FileEntry[]>) {
  const checked = ref<Set<string>>(new Set());
  const anchor = ref<string | null>(null);

  const allChecked = computed(() => files.value.length > 0 && files.value.every(file => checked.value.has(file.path)));

  function toggle(file: FileEntry) {
    const next = new Set(checked.value);
    if (!next.delete(file.path)) {
      next.add(file.path);
    }
    checked.value = next;
    anchor.value = file.path;
  }

  function range(file: FileEntry) {
    const paths = files.value.map(entry => entry.path);
    const from = anchor.value ? paths.indexOf(anchor.value) : -1;
    const to = paths.indexOf(file.path);

    if (from < 0 || to < 0) {
      toggle(file);
      return;
    }

    checked.value = new Set([...checked.value, ...paths.slice(Math.min(from, to), Math.max(from, to) + 1)]);
  }

  function toggleAll() {
    checked.value = allChecked.value ? new Set() : new Set(files.value.map(file => file.path));
  }

  function keepOnly(present: Set<string>) {
    const kept = [...checked.value].filter(path => present.has(path));
    if (kept.length !== checked.value.size) {
      checked.value = new Set(kept);
    }
  }

  function clear() {
    checked.value = new Set();
    anchor.value = null;
  }

  return { checked, allChecked, toggle, range, toggleAll, keepOnly, clear };
}
