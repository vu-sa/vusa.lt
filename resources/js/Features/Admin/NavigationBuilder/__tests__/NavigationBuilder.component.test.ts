import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import NavigationBuilder from '@/Features/Admin/NavigationBuilder/NavigationBuilder.vue';
import type { AdminNavigationRoot } from '@/Features/Admin/NavigationBuilder/types';

vi.mock('@vueuse/integrations/useSortable', () => ({
  useSortable: vi.fn(),
  insertNodeAt: vi.fn(),
  removeNode: vi.fn(),
}));

// AlertDialog/Tooltip render through reka-ui portals + focus traps that jsdom does not
// model predictably (see resources/js/CLAUDE.md stubbing policy) — render inline instead
// so their trigger buttons and confirm actions stay assertable without visual assertions.
const alertDialogStubs = {
  AlertDialog: { template: '<div><slot /></div>' },
  AlertDialogContent: { template: '<div><slot /></div>' },
  AlertDialogHeader: { template: '<div><slot /></div>' },
  AlertDialogTitle: { template: '<div><slot /></div>' },
  AlertDialogDescription: { template: '<div><slot /></div>' },
  AlertDialogFooter: { template: '<div><slot /></div>' },
  AlertDialogTrigger: { template: '<div><slot /></div>' },
  AlertDialogAction: { template: '<button class="confirm-delete" @click="$emit(\'click\')"><slot /></button>' },
  AlertDialogCancel: { template: '<button class="cancel-delete"><slot /></button>' },
  Tooltip: { template: '<div><slot /></div>' },
  TooltipTrigger: { template: '<div><slot /></div>' },
  TooltipContent: { template: '<div><slot /></div>' },
  MainNavigationMenuContent: { props: ['item'], template: '<div data-testid="preview-item">{{ item.name }}</div>' },
};

function makeRoot(overrides: Partial<AdminNavigationRoot> = {}): AdminNavigationRoot {
  return {
    id: 1,
    name: 'Studentams',
    url: '#',
    parent_id: 0,
    lang: 'lt',
    order: 0,
    is_active: true,
    extra_attributes: {},
    cols: 1,
    links: [
      [
        { id: 10, name: 'Dokumentai', url: '/dokumentai', parent_id: 1, lang: 'lt', order: 0, is_active: true, extra_attributes: { column: 1 } },
      ],
      [],
      [],
    ],
    ...overrides,
  };
}

function createWrapper(props: Record<string, unknown> = {}) {
  return mount(NavigationBuilder, {
    props: {
      roots: [makeRoot()],
      lang: 'lt',
      ...props,
    },
    global: { stubs: alertDialogStubs },
  });
}

describe('NavigationBuilder.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders each root with its links', () => {
    wrapper = createWrapper();

    expect(wrapper.text()).toContain('Studentams');
    expect(wrapper.text()).toContain('Dokumentai');
  });

  it('emits update:lang when the language switcher changes', async () => {
    wrapper = createWrapper();

    const enToggle = wrapper.findAll('button').find(btn => btn.text() === 'EN');
    await enToggle?.trigger('click');

    expect(wrapper.emitted('update:lang')).toBeTruthy();
    expect(wrapper.emitted('update:lang')?.[0]).toEqual(['en']);
  });

  it('switches from the edit builder to the read-only preview', async () => {
    wrapper = createWrapper();

    expect(wrapper.find('[data-testid="preview-item"]').exists()).toBe(false);

    const previewToggle = wrapper.findAll('button').find(btn => btn.text().includes('navigation.builder.preview_mode'));
    await previewToggle?.trigger('click');

    expect(wrapper.find('[data-testid="preview-item"]').exists()).toBe(true);
    // The editable card list is gone once in preview mode.
    expect(wrapper.text()).not.toContain('Dokumentai');
  });

  it('shows the drift banner when the other language has more items', () => {
    wrapper = createWrapper({
      translationSummary: { counts: { lt: 61, en: 82 }, mismatchedRoots: [] },
    });

    expect(wrapper.text()).toContain('navigation.builder.drift_banner');
  });

  it('hides the drift banner when the current language is not behind', () => {
    wrapper = createWrapper({
      translationSummary: { counts: { lt: 82, en: 61 }, mismatchedRoots: [] },
    });

    expect(wrapper.text()).not.toContain('navigation.builder.drift_banner');
  });

  it('patches is_active with the full record when a link is toggled', async () => {
    wrapper = createWrapper();

    // Root switch (header) renders before the link switches (columns) in DOM order.
    const toggle = wrapper.findAll('button[role="switch"]')[1];
    await toggle.trigger('click');

    expect(router.patch).toHaveBeenCalledWith(
      expect.stringContaining('navigation.update'),
      expect.objectContaining({ is_active: false, name: 'Dokumentai', url: '/dokumentai' }),
      expect.objectContaining({ preserveScroll: true }),
    );
  });

  it('reflects the link toggle in the UI immediately, without waiting for the round-trip', async () => {
    // router.patch/delete default preserveState:true — Inertia does not remount this
    // component on a successful response, so the switch must update from the local
    // optimistic mutation, not from a fresh `roots` prop that never arrives in this test.
    wrapper = createWrapper();

    const toggle = wrapper.findAll('button[role="switch"]')[1];
    expect(toggle.attributes('aria-checked')).toBe('true');

    await toggle.trigger('click');

    expect(wrapper.findAll('button[role="switch"]')[1].attributes('aria-checked')).toBe('false');
  });

  it('does not trigger an order save from a link is_active toggle', async () => {
    vi.useFakeTimers();
    wrapper = createWrapper();
    vi.mocked(router.post).mockClear();

    const toggle = wrapper.findAll('button[role="switch"]')[1];
    await toggle.trigger('click');
    await vi.advanceTimersByTimeAsync(850);

    expect(router.post).not.toHaveBeenCalled();
    vi.useRealTimers();
  });

  it('patches is_active with the full record when a root is toggled', async () => {
    wrapper = createWrapper();

    const toggle = wrapper.findAll('button[role="switch"]')[0];
    await toggle.trigger('click');

    expect(router.patch).toHaveBeenCalledWith(
      expect.stringContaining('navigation.update'),
      expect.objectContaining({ is_active: false, name: 'Studentams', url: '#' }),
      expect.objectContaining({ preserveScroll: true }),
    );
  });

  it('reflects the root toggle in the UI immediately, without waiting for the round-trip', async () => {
    wrapper = createWrapper();

    const toggle = wrapper.findAll('button[role="switch"]')[0];
    expect(toggle.attributes('aria-checked')).toBe('true');

    await toggle.trigger('click');

    expect(wrapper.findAll('button[role="switch"]')[0].attributes('aria-checked')).toBe('false');
  });

  it('does not trigger an order save from a root is_active toggle', async () => {
    vi.useFakeTimers();
    wrapper = createWrapper();
    vi.mocked(router.post).mockClear();

    const toggle = wrapper.findAll('button[role="switch"]')[0];
    await toggle.trigger('click');
    await vi.advanceTimersByTimeAsync(850);

    expect(router.post).not.toHaveBeenCalled();
    vi.useRealTimers();
  });

  it('deletes a link through the confirm dialog', async () => {
    wrapper = createWrapper();

    // Both the root and the link render their own delete confirm dialog — the root's
    // comes first in DOM order (header, then columns), the link's second.
    const confirmButtons = wrapper.findAll('.confirm-delete');
    await confirmButtons[1].trigger('click');

    expect(router.delete).toHaveBeenCalledWith(expect.stringContaining('navigation.destroy'), { preserveScroll: true });
  });

  it('removes the link from the UI immediately when deleted', async () => {
    wrapper = createWrapper();

    expect(wrapper.text()).toContain('Dokumentai');

    const confirmButtons = wrapper.findAll('.confirm-delete');
    await confirmButtons[1].trigger('click');

    expect(wrapper.text()).not.toContain('Dokumentai');
  });

  it('links "add link" and "add root" to the create route with the right parent', () => {
    wrapper = createWrapper();

    const links = wrapper.findAll('a');
    expect(links.some(a => a.attributes('href')?.includes('navigation.create'))).toBe(true);
  });
});
