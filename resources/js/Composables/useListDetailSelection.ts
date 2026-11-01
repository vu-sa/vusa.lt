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
  /** The detail beside the list: ↑/↓ keep walking from its controls, while Enter stays theirs. */
  detailSelector?: string;
  onEnter?: (item: T) => void;
  /** After a keyboard move, e.g. to scroll the row into view. */
  onNavigate?: (key: string) => void;
}

const FIELD_TAGS = ['INPUT', 'TEXTAREA', 'SELECT'];
const CONTROLS = 'button, a, [role="button"], [role="checkbox"], [role="combobox"], [role="menuitem"]';

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

  /** Keys belong to the list unless they come from outside it or from a control of their own. */
  function ownsEvent(event: KeyboardEvent, target: HTMLElement | null): boolean {
    if (!isEnabled() || event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey) {
      return false;
    }
    const root = toValue(options.root);
    if (root && target && target !== document.body && !root.contains(target)) {
      return false;
    }
    const isRow = Boolean(options.rowSelector && target?.closest(options.rowSelector));
    const isDetailArrow = event.key !== 'Enter' && Boolean(options.detailSelector && target?.closest(options.detailSelector));

    return isRow || isDetailArrow || !target?.closest(CONTROLS);
  }

  function open(event: KeyboardEvent, isField: boolean): void {
    const selected = toValue(options.selectedKey);
    const item = toValue(options.items).find(candidate => options.itemKey(candidate) === selected);
    if (isField || !item || !options.onEnter) {
      return;
    }
    event.preventDefault();
    options.onEnter(item);
  }

  function move(event: KeyboardEvent, delta: number): void {
    const items = toValue(options.items);
    if (items.length === 0) {
      return;
    }
    event.preventDefault();

    const selected = toValue(options.selectedKey);
    const currentIndex = items.findIndex(item => options.itemKey(item) === selected);
    const nextKey = options.itemKey(items[Math.min(items.length - 1, Math.max(0, currentIndex + delta))]);

    if (nextKey !== selected) {
      options.select(nextKey);
      options.onNavigate?.(nextKey);
    }
  }

  useEventListener('keydown', (event: KeyboardEvent) => {
    const target = event.target as HTMLElement | null;
    if (!['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key) || !ownsEvent(event, target)) {
      return;
    }

    const isField = Boolean(target && (target.isContentEditable || FIELD_TAGS.includes(target.tagName)));
    if (event.key === 'Enter') {
      open(event, isField);
    }
    else if (!isField || (options.arrowsFromInput && target?.tagName === 'INPUT')) {
      move(event, event.key === 'ArrowDown' ? 1 : -1);
    }
  });
}
