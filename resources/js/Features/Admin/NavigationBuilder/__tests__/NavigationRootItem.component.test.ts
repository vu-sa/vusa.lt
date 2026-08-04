import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h, nextTick } from 'vue';

import NavigationRootItem from '@/Features/Admin/NavigationBuilder/NavigationRootItem.vue';
import type { AdminNavigationRoot } from '@/Features/Admin/NavigationBuilder/types';
import { TooltipProvider } from '@/Components/ui/tooltip';

// `@vueuse/integrations`' useSortable only wires SortableJS's `onUpdate` (same-list
// reorder) — it has no support for moving an item between two different lists (see
// its source: the only default option is `onUpdate: moveArrayElement`). Real
// SortableJS drag physics can't be simulated in jsdom, so this test captures the
// `onEnd` callback NavigationRootItem registers and invokes it directly with a
// synthetic event, exercising the exact cross-column splice logic a real drag would
// trigger — this is what regressed silently before (drags between columns did
// nothing because nothing implemented onAdd/onRemove/onEnd for the cross-list case).
// The component now registers `useSortable` at setup top level against a `Ref`
// (rather than a resolved element grabbed inside `onMounted`, which vueuse silently
// never called) — the mock captures that ref and resolves `.value` lazily, after
// mount, once the component's own ref callback has populated it.
const registeredSortables: Array<{ elRef: { value: HTMLElement | null }; onEnd: (evt: unknown) => void; onMove: (evt: unknown) => boolean }> = [];

// Only `useSortable` itself is mocked — `insertNodeAt`/`removeNode` stay real, because
// the DOM bookkeeping they do is where the cross-column duplicate bug actually lived
// (stubbing them out hid it completely, and the model-level assertions below all passed
// while the UI left a copy of the card behind).
vi.mock('@vueuse/integrations/useSortable', async (importOriginal) => ({
  ...await importOriginal<typeof import('@vueuse/integrations/useSortable')>(),
  useSortable: vi.fn((elRef: { value: HTMLElement | null }, _list: unknown, options: { onEnd: (evt: unknown) => void; onMove: (evt: unknown) => boolean }) => {
    registeredSortables.push({ elRef, onEnd: options.onEnd, onMove: options.onMove });
    return { stop: vi.fn(), start: vi.fn(), option: vi.fn() };
  }),
}));

/** The ids of the cards actually rendered in a column, in DOM order. */
function renderedIds(column: HTMLElement): string[] {
  return Array.from(column.querySelectorAll('.nav-link-card')).map(el => (el as HTMLElement).dataset.linkId!);
}

/**
 * Real SortableJS has already moved the dragged node into the target container by the
 * time `onEnd` fires — reproducing that is the whole point, since the handler's job is
 * to undo it and let Vue re-render from the reordered arrays instead.
 */
function simulateDrop(from: HTMLElement, to: HTMLElement, linkId: string, newIndex: number) {
  const item = from.querySelector(`[data-link-id="${linkId}"]`) as HTMLElement;
  const oldIndex = Array.from(from.children).indexOf(item);
  to.appendChild(item);
  return { item, from, to, oldIndex, newIndex };
}

const alertDialogStubs = {
  AlertDialog: { template: '<div><slot /></div>' },
  AlertDialogContent: { template: '<div><slot /></div>' },
  AlertDialogHeader: { template: '<div><slot /></div>' },
  AlertDialogTitle: { template: '<div><slot /></div>' },
  AlertDialogDescription: { template: '<div><slot /></div>' },
  AlertDialogFooter: { template: '<div><slot /></div>' },
  AlertDialogTrigger: { template: '<div><slot /></div>' },
  AlertDialogAction: { template: '<button class="confirm-delete"><slot /></button>' },
  AlertDialogCancel: { template: '<button class="cancel-delete"><slot /></button>' },
  Tooltip: { template: '<div><slot /></div>' },
  TooltipTrigger: { template: '<div><slot /></div>' },
  TooltipContent: { template: '<div><slot /></div>' },
};

function makeRoot(): AdminNavigationRoot {
  return {
    id: 1,
    name: 'Studentams',
    url: '#',
    parent_id: 0,
    lang: 'lt',
    order: 0,
    is_active: true,
    extra_attributes: {},
    cols: 2,
    links: [
      [
        { id: 10, name: 'Dokumentai', url: '/a', parent_id: 1, lang: 'lt', order: 0, is_active: true, extra_attributes: { column: 1 } },
        { id: 11, name: 'Studijos', url: '/b', parent_id: 1, lang: 'lt', order: 1, is_active: true, extra_attributes: { column: 1 } },
      ],
      [
        { id: 20, name: 'Karjera', url: '/c', parent_id: 1, lang: 'lt', order: 0, is_active: true, extra_attributes: { column: 2 } },
      ],
      [],
    ],
  };
}

describe('NavigationRootItem.vue — cross-column drag', () => {
  beforeEach(() => {
    registeredSortables.length = 0;
  });

  it('registers one SortableJS instance per column', () => {
    mount(NavigationRootItem, {
      props: { root: makeRoot() },
      global: { stubs: alertDialogStubs },
    });

    expect(registeredSortables).toHaveLength(3);
  });

  it('moves a link from one column into another on drag end', async () => {
    const root = makeRoot();
    mount(NavigationRootItem, {
      props: { root },
      attachTo: document.body,
      global: { stubs: alertDialogStubs },
    });
    // Function template refs resolve through Vue's post-render effect queue, one tick
    // after `mount()` returns — without this, `elRef.value` (read below) is still null.
    await nextTick();

    const [column1, column2] = registeredSortables;
    const from = column1.elRef.value!;
    const to = column2.elRef.value!;

    // Drag "Studijos" (id 11) from column 1 into column 2, landing after "Karjera".
    column1.onEnd(simulateDrop(from, to, '11', 1));

    // The insert into the target column is deferred to the next tick (mirroring
    // vueuse's own moveArrayElement) to avoid a race with the manual DOM revert.
    expect(root.links[0].map(l => l.id)).toEqual([10]);
    await nextTick();
    expect(root.links[1].map(l => l.id)).toEqual([20, 11]);

    // …and the rendered columns must agree with the arrays. This is the assertion that
    // failed while the card rendered as a Fragment: the model said the link had moved,
    // but the dragged node stayed behind in the source column as a visual duplicate.
    await nextTick();
    expect(renderedIds(from)).toEqual(['10']);
    expect(renderedIds(to)).toEqual(['20', '11']);
  });

  it('reorders within a single column on drag end', async () => {
    const root = makeRoot();
    mount(NavigationRootItem, {
      props: { root },
      attachTo: document.body,
      global: { stubs: alertDialogStubs },
    });
    await nextTick();

    const [column1] = registeredSortables;
    const from = column1.elRef.value!;

    // Move "Dokumentai" (id 10) to the end of the same column.
    column1.onEnd(simulateDrop(from, from, '10', 1));

    // The insert (into this same column) is deferred to the next tick — see the
    // cross-column test above.
    await nextTick();
    expect(root.links[0].map(l => l.id)).toEqual([11, 10]);

    await nextTick();
    expect(renderedIds(from)).toEqual(['11', '10']);
  });

  it('drops a link into an empty column without leaving the card behind', async () => {
    const root = makeRoot();
    mount(NavigationRootItem, {
      props: { root },
      attachTo: document.body,
      global: { stubs: alertDialogStubs },
    });
    await nextTick();

    const [column1, , column3] = registeredSortables;
    const from = column1.elRef.value!;
    const to = column3.elRef.value!;

    // An empty column renders a placeholder `<p>`, and SortableJS's `newIndex` is a raw
    // element index that counts it — so a drop past the placeholder reports 1 for what
    // is really position 0 in an empty array. `splice(1, 0, x)` on `[]` still lands the
    // link at index 0, so the off-by-one is harmless; this pins that down.
    column1.onEnd(simulateDrop(from, to, '11', 1));

    await nextTick();
    await nextTick();
    expect(renderedIds(from)).toEqual(['10']);
    expect(renderedIds(to)).toEqual(['11']);
  });
});

describe('NavigationRootItem.vue — column layout', () => {
  // Each column is a grid item, so its automatic minimum size is `min-content` — and a
  // link card's min-content is wide, because the name and URL inside are `truncate`d
  // (`white-space: nowrap`), which caps how narrow the card renders but not what it
  // contributes. Measured in a browser at a 390px viewport, that grew the auto track to
  // ~390px inside a ~356px container and pushed the column (most visibly a full-height
  // card's background image) off the right edge. jsdom does no layout, so this asserts
  // the class that zeroes the minimum rather than a measured width — the visual side is
  // covered by NavigationBuilder.stories.ts.
  it('lets each column shrink below its cards\' min-content width', async () => {
    const wrapper = mount(NavigationRootItem, {
      props: { root: makeRoot() },
      global: { stubs: alertDialogStubs },
    });
    await nextTick();

    const columns = wrapper.findAll('[data-column]');
    expect(columns).toHaveLength(3);
    columns.forEach(column => expect(column.classes()).toContain('min-w-0'));
  });
});

describe('NavigationRootItem.vue — full-height-background-link drag restriction', () => {
  beforeEach(() => {
    registeredSortables.length = 0;
  });

  function makeRootWithFullHeight(): AdminNavigationRoot {
    return {
      id: 2,
      name: 'Root',
      url: '#',
      parent_id: 0,
      lang: 'lt',
      order: 0,
      is_active: true,
      extra_attributes: {},
      cols: 3,
      links: [
        [{ id: 30, name: 'Hero', url: '/hero', parent_id: 2, lang: 'lt', order: 0, is_active: true, extra_attributes: { type: 'full-height-background-link', column: 1 } }],
        [{ id: 31, name: 'Regular', url: '/reg', parent_id: 2, lang: 'lt', order: 0, is_active: true, extra_attributes: { column: 2 } }],
        [],
      ],
    };
  }

  it('allows dragging a full-height item into an empty column', async () => {
    mount(NavigationRootItem, { props: { root: makeRootWithFullHeight() }, global: { stubs: alertDialogStubs } });
    await nextTick();

    const [column1, , column3] = registeredSortables;
    const draggedFullHeight = { dataset: { linkId: '30' } } as unknown as HTMLElement;

    expect(column1.onMove({ dragged: draggedFullHeight, from: column1.elRef.value, to: column3.elRef.value })).toBe(true);
  });

  it('blocks dragging a full-height item into a column that already has other items', async () => {
    mount(NavigationRootItem, { props: { root: makeRootWithFullHeight() }, global: { stubs: alertDialogStubs } });
    await nextTick();

    const [column1, column2] = registeredSortables;
    const draggedFullHeight = { dataset: { linkId: '30' } } as unknown as HTMLElement;

    expect(column1.onMove({ dragged: draggedFullHeight, from: column1.elRef.value, to: column2.elRef.value })).toBe(false);
  });

  it('blocks dragging a regular item into a column that already holds a full-height item', async () => {
    mount(NavigationRootItem, { props: { root: makeRootWithFullHeight() }, global: { stubs: alertDialogStubs } });
    await nextTick();

    const [column1, column2] = registeredSortables;
    const draggedRegular = { dataset: { linkId: '31' } } as unknown as HTMLElement;

    expect(column2.onMove({ dragged: draggedRegular, from: column2.elRef.value, to: column1.elRef.value })).toBe(false);
  });

  it('allows dragging a regular item into an empty column', async () => {
    mount(NavigationRootItem, { props: { root: makeRootWithFullHeight() }, global: { stubs: alertDialogStubs } });
    await nextTick();

    const [, column2, column3] = registeredSortables;
    const draggedRegular = { dataset: { linkId: '31' } } as unknown as HTMLElement;

    expect(column2.onMove({ dragged: draggedRegular, from: column2.elRef.value, to: column3.elRef.value })).toBe(true);
  });

  it('always allows a same-column move regardless of type', async () => {
    mount(NavigationRootItem, { props: { root: makeRootWithFullHeight() }, global: { stubs: alertDialogStubs } });
    await nextTick();

    const [column1] = registeredSortables;
    const draggedFullHeight = { dataset: { linkId: '30' } } as unknown as HTMLElement;

    expect(column1.onMove({ dragged: draggedFullHeight, from: column1.elRef.value, to: column1.elRef.value })).toBe(true);
  });
});

describe('NavigationRootItem.vue — active toggle', () => {
  it('emits toggle-active with the root and new value when its switch is flipped', async () => {
    const root = makeRoot();
    const wrapper = mount(NavigationRootItem, {
      props: { root },
      global: { stubs: alertDialogStubs },
    });

    const toggle = wrapper.find('button[role="switch"]');
    await toggle.trigger('click');

    expect(wrapper.emitted('toggle-active')?.[0]).toEqual([root, false]);
  });

  // Deliberately uses the real Tooltip + TooltipProvider (not the stubbed versions
  // above) — this is a regression test for a real bug where TooltipTrigger's
  // `as-child` merged its own `data-state` (open/closed) onto the Switch it wrapped,
  // clobbering the Switch's own `checked`/`unchecked` state and silently blanking
  // out its color classes. A stubbed Tooltip can't exercise that merge at all.
  it('keeps its own data-state (not the Tooltip trigger\'s) on the root switch', async () => {
    const root = makeRoot();
    const Wrapper = defineComponent({
      setup() {
        return () => h(TooltipProvider, null, { default: () => h(NavigationRootItem, { root }) });
      },
    });
    const wrapper = mount(Wrapper);
    await nextTick();

    const toggle = wrapper.find('button[role="switch"]');
    expect(toggle.attributes('data-state')).toBe('checked');
  });
});
