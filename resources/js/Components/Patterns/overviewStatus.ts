import { ref, shallowReactive, type Component, type InjectionKey, type Ref } from 'vue';

export interface OverviewStatusEntry {
  title: string;
  emptyText?: string;
  icon?: Component;
}

/**
 * Empty sections of an overview page, gathered into one quiet list — at the page's end, unless the
 * page places an `<OverviewStatusList />` of its own (`placed` > 0).
 */
export interface OverviewStatusRegistry {
  entries: Map<string, OverviewStatusEntry>;
  placed: Ref<number>;
  set: (id: string, entry: OverviewStatusEntry) => void;
  remove: (id: string) => void;
}

export const OVERVIEW_STATUS_KEY: InjectionKey<OverviewStatusRegistry> = Symbol('overview-status');

export function createOverviewStatusRegistry(): OverviewStatusRegistry {
  // Shallow: entries hold icon components, which must not be made reactive.
  const entries = shallowReactive(new Map<string, OverviewStatusEntry>());

  return {
    entries,
    placed: ref(0),
    set: (id, entry) => entries.set(id, entry),
    remove: id => entries.delete(id),
  };
}
