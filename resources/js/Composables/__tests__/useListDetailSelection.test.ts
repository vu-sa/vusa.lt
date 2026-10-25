import { afterEach, describe, expect, it, vi } from 'vitest';
import { effectScope, nextTick, ref, type EffectScope } from 'vue';

import { useListDetailSelection } from '../useListDetailSelection';

interface Item {
  id: string;
}

let scope: EffectScope | undefined;

function setup(overrides: Partial<Parameters<typeof useListDetailSelection<Item>>[0]> = {}) {
  const items = ref<Item[]>([{ id: 'a' }, { id: 'b' }, { id: 'c' }]);
  const selected = ref<string | null>(null);
  const enabled = ref(true);
  const onEnter = vi.fn();
  const onNavigate = vi.fn();

  scope = effectScope();
  scope.run(() => useListDetailSelection<Item>({
    items,
    itemKey: item => item.id,
    selectedKey: selected,
    select: (key) => {
      selected.value = key;
    },
    enabled,
    onEnter,
    onNavigate,
    ...overrides,
  }));

  return { items, selected, enabled, onEnter, onNavigate };
}

function press(key: string, target: EventTarget = document.body) {
  const event = new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true });
  target.dispatchEvent(event);
  return event;
}

afterEach(() => {
  scope?.stop();
  document.body.innerHTML = '';
});

describe('useListDetailSelection', () => {
  it('opens the first item, and again when the open one leaves the list', async () => {
    const { items, selected } = setup();
    expect(selected.value).toBe('a');

    items.value = [{ id: 'c' }];
    await nextTick();
    expect(selected.value).toBe('c');

    items.value = [];
    await nextTick();
    expect(selected.value).toBeNull();
  });

  it('walks the list with the arrow keys and stops at its ends', () => {
    const { selected, onNavigate } = setup();

    press('ArrowDown');
    press('ArrowDown');
    press('ArrowDown');
    expect(selected.value).toBe('c');
    expect(onNavigate).toHaveBeenCalledTimes(2);

    press('ArrowUp');
    expect(selected.value).toBe('b');
  });

  it('hands Enter on the open item to the caller', () => {
    const { onEnter } = setup();

    expect(press('Enter').defaultPrevented).toBe(true);
    expect(onEnter).toHaveBeenCalledWith({ id: 'a' });
  });

  it('does nothing while disabled', async () => {
    const { selected, enabled } = setup();
    enabled.value = false;
    await nextTick();

    const event = press('ArrowDown');
    expect(event.defaultPrevented).toBe(false);
    expect(selected.value).toBe('a');
  });

  it('leaves keys alone in fields, controls and outside its root', () => {
    const root = document.createElement('div');
    document.body.append(root);
    const { selected, onEnter } = setup({ root });

    const textarea = document.createElement('textarea');
    const button = document.createElement('button');
    const outside = document.createElement('div');
    outside.tabIndex = 0;
    root.append(textarea, button);
    document.body.append(outside);

    press('ArrowDown', textarea);
    press('ArrowDown', button);
    press('ArrowDown', outside);
    press('Enter', textarea);

    expect(selected.value).toBe('a');
    expect(onEnter).not.toHaveBeenCalled();
  });

  it('moves from a search input only when asked to, and from a row\'s own link', () => {
    const input = document.createElement('input');
    const row = document.createElement('a');
    row.dataset.collectionOpen = '';
    document.body.append(input, row);

    const plain = setup();
    press('ArrowDown', input);
    expect(plain.selected.value).toBe('a');
    scope?.stop();

    const picker = setup({ arrowsFromInput: true, rowSelector: '[data-collection-open]' });
    press('ArrowDown', input);
    expect(picker.selected.value).toBe('b');
    press('ArrowDown', row);
    expect(picker.selected.value).toBe('c');
  });
});
