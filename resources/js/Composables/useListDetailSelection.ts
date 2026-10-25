import { useEventListener } from '@vueuse/core';
import { toValue, watch, type MaybeRefOrGetter } from 'vue';

interface ListDetailSelectionOptions<T> {
  items: MaybeRefOrGetter<readonly T[]>;
  itemKey: (item: T) => string;
  selectedKey: MaybeRefOrGetter<string | null | undefined>;
  /** `null` when the list empties; a caller whose list refills (a URL-restored `?item=`) may ignore it. */
  select: (key: string | null) => void;
  /** Off turns both the auto-selection and the keys off (e.g. a collection outside its preview view). */
  enabled?: MaybeRefOrGetter<boolean>;
  /** Keys are handled only for events from inside this element or from the page body. */
  root?: MaybeRefOrGetter<HTMLElement | null | undefined>;
  /** ↑/↓ still move from a text input, so a picker's search box drives the list. */
  arrowsFromInput?: boolean;
  /** A row's own link or button: keys from it move the list instead of being left to the control. */
  rowSelector?: string;
  onEnter?: (item: T) => void;
  /** After a keyboard move, e.g. to scroll the row into view. */
  onNavigate?: (key: string) => void;
}

const FIELD_TAGS = ['INPUT', 'TEXTAREA', 'SELECT'];

/**
 * The list + detail contract shared by the collection preview view and the search split view:
 * something is always open while there is something to show, and ↑/↓/Enter work from the page.
 */
export function useListDetailSelection<T>(options: ListDetailSelectionOptions<T>): void {
  const isEnabled = () => toValue(options.enabled) ?? true;

  watch(
    () => [isEnabled(), toValue(options.items)] as const,
    ([enabled, items]) => {
      if (!enabled) {
        return;
      }
      if (items.length === 0) {
        if (toValue(options.selectedKey)) {
          options.select(null);
        }
        return;
      }
      const selected = toValue(options.selectedKey);
      if (!items.some(item => options.itemKey(item) === selected)) {
        options.select(options.itemKey(items[0]));
      }
    },
    { immediate: true },
  );

  useEventListener('keydown', (event: KeyboardEvent) => {
    if (!isEnabled() || !['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key) || event.defaultPrevented) {
      return;
    }
    if (event.altKey || event.ctrlKey || event.metaKey) {
      return;
    }

    const target = event.target as HTMLElement | null;
    const root = toValue(options.root);
    if (root && target && target !== document.body && !root.contains(target)) {
      return;
    }
    const isRow = Boolean(options.rowSelector && target?.closest(options.rowSelector));
    if (!isRow && target?.closest('button, a, [role="button"], [role="checkbox"], [role="combobox"], [role="menuitem"]')) {
      return;
    }

    const isField = Boolean(target && (target.isContentEditable || FIELD_TAGS.includes(target.tagName)));
    const items = toValue(options.items);
    const selected = toValue(options.selectedKey);

    if (event.key === 'Enter') {
      const item = items.find(candidate => options.itemKey(candidate) === selected);
      if (isField || !item || !options.onEnter) {
        return;
      }
      event.preventDefault();
      options.onEnter(item);
      return;
    }

    if ((isField && !(options.arrowsFromInput && target?.tagName === 'INPUT')) || items.length === 0) {
      return;
    }

    event.preventDefault();
    const currentIndex = items.findIndex(item => options.itemKey(item) === selected);
    const delta = event.key === 'ArrowDown' ? 1 : -1;
    const nextKey = options.itemKey(items[Math.min(items.length - 1, Math.max(0, currentIndex + delta))]);

    if (nextKey !== selected) {
      options.select(nextKey);
      options.onNavigate?.(nextKey);
    }
  });
}
