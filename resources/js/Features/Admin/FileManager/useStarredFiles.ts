import { computed } from 'vue';
import { useStorage } from '@vueuse/core';

import type { FileEntry } from './types';

/**
 * Starred files, kept per browser. Whole entries are stored, not paths, so the starred view can
 * list files from other folders; they follow renames and moves made here.
 */
export function useStarredFiles() {
  const entries = useStorage<FileEntry[]>('fileManager-starredFiles', []);
  const paths = computed(() => new Set(entries.value.map(entry => entry.path)));

  migrateBarePaths(entries.value, (migrated) => {
    entries.value = migrated;
  });

  function star(files: FileEntry[], directory: string) {
    const fresh = files.filter(file => !paths.value.has(file.path));
    entries.value = [...entries.value, ...fresh.map(file => ({ ...file, directory: file.directory ?? directory }))];
    return fresh.length;
  }

  function toggle(file: FileEntry, directory: string) {
    if (paths.value.has(file.path)) {
      forget([file.path]);
    }
    else {
      star([file], directory);
    }
  }

  function forget(removed: string[]) {
    const gone = new Set(removed);
    entries.value = entries.value.filter(entry => !gone.has(entry.path));
  }

  function rename(from: string, to: string) {
    entries.value = entries.value.map(entry => (entry.path === from
      ? { ...entry, path: to, name: to.split('/').pop() ?? entry.name }
      : entry));
  }

  function move(moved: string[], destination: string) {
    const set = new Set(moved);
    entries.value = entries.value.map(entry => (set.has(entry.path)
      ? { ...entry, path: `${destination}/${entry.name}`, directory: destination }
      : entry));
  }

  return { entries, paths, star, toggle, forget, rename, move };
}

/** Stars were once stored as bare paths; keep them, with what the path alone can tell. */
function migrateBarePaths(current: FileEntry[], save: (entries: FileEntry[]) => void) {
  const legacy = useStorage<string[]>('fileManager-starred', []);
  if (legacy.value.length === 0) return;

  const known = new Set(current.map(entry => entry.path));
  save([
    ...current,
    ...legacy.value.filter(path => !known.has(path)).map(path => ({
      path,
      name: path.split('/').pop() ?? path,
      type: 'file' as const,
      size: 0,
      modified: 0,
      directory: path.split('/').slice(0, -1).join('/'),
    })),
  ]);
  legacy.value = [];
}
