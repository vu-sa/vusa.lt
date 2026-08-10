import { describe, it, expect, afterEach, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import DataTableActions from '@/Components/ui/data-table/DataTableActions.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

interface TestModel {
  id: number;
  name: string;
  deleted_at?: string | null;
  force_delete_blocked_reason?: string | null;
}

describe('DataTableActions', () => {
  let wrapper: ReturnType<typeof mount>;

  const mountActions = (props: Record<string, unknown> = {}) => mount(DataTableActions, {
    props: {
      model: { id: 1, name: 'Test record', deleted_at: '2026-07-27T00:00:00.000000Z' } satisfies TestModel,
      modelName: 'tests',
      ...props,
    },
    global: {
      stubs: {
        ...commonStubs,
        DialogClose: { template: '<div><slot /></div>' },
      },
    },
  });

  const liveModel = { id: 1, name: 'Test record', deleted_at: null } satisfies TestModel;

  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ app: { locale: 'lt' } }));
  });

  afterEach(() => {
    wrapper?.unmount();
    vi.clearAllMocks();
  });

  describe('inline actions', () => {
    it('renders view, edit and duplicate directly in the cell rather than in the menu', () => {
      wrapper = mountActions({
        model: liveModel,
        canView: true,
        canEdit: true,
        canDuplicate: true,
      });

      expect(wrapper.find('[data-testid="row-action-view"]').exists()).toBe(true);
      expect(wrapper.find('[data-testid="row-action-edit"]').exists()).toBe(true);
      expect(wrapper.find('[data-testid="row-action-duplicate"]').exists()).toBe(true);
      expect(wrapper.find('[data-testid="dropdown-menu"]').exists()).toBe(false);
    });

    it('offers only restore inline for a trashed row', () => {
      wrapper = mountActions({ canView: true, canEdit: true, canRestore: true });

      expect(wrapper.find('[data-testid="row-action-restore"]').exists()).toBe(true);
      expect(wrapper.find('[data-testid="row-action-view"]').exists()).toBe(false);
      expect(wrapper.find('[data-testid="row-action-edit"]').exists()).toBe(false);
    });

    it('labels inline actions with translated keys', () => {
      wrapper = mountActions({ model: liveModel, canView: true, canEdit: true });

      expect(wrapper.text()).toContain('tables.view');
      expect(wrapper.text()).toContain('forms.edit');
    });

    // Anchors are what make middle-click, ctrl-click and "copy link address"
    // work; a button handled by router.visit() supports none of them.
    it('renders navigational actions as anchors carrying their route', () => {
      wrapper = mountActions({
        model: liveModel,
        canView: true,
        canEdit: true,
        viewRoute: '/mano/tests/1',
        editRoute: '/mano/tests/1/edit',
      });

      const view = wrapper.find('[data-testid="row-action-view"]');
      const edit = wrapper.find('[data-testid="row-action-edit"]');

      expect(view.element.tagName).toBe('A');
      expect(view.attributes('href')).toBe('/mano/tests/1');
      expect(edit.element.tagName).toBe('A');
      expect(edit.attributes('href')).toBe('/mano/tests/1/edit');
    });

    it('keeps non-GET actions as buttons', () => {
      wrapper = mountActions({
        model: liveModel,
        canDuplicate: true,
        duplicateRoute: '/mano/tests/1/duplicate',
      });

      expect(wrapper.find('[data-testid="row-action-duplicate"]').element.tagName).toBe('BUTTON');
    });

    it('still emits the action when a navigational anchor is clicked', async () => {
      wrapper = mountActions({ model: liveModel, canView: true, viewRoute: '/mano/tests/1' });

      await wrapper.find('[data-testid="row-action-view"]').trigger('click');

      expect(wrapper.emitted('action')?.[0]).toEqual(['view', liveModel]);
    });
  });

  describe('destructive actions', () => {
    it('keeps delete behind the overflow menu', () => {
      wrapper = mountActions({ model: liveModel, canView: true, canDelete: true });

      expect(wrapper.find('[data-testid="row-actions-overflow"]').exists()).toBe(true);
      expect(wrapper.find('[data-testid="row-action-delete"]').exists()).toBe(true);
    });

    it('shows permanent delete only for trashed rows when force-delete is allowed', () => {
      wrapper = mountActions({ canForceDelete: true });

      expect(wrapper.find('[data-testid="row-action-force-delete"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('trash.permanently_delete');
    });

    it('hides permanent delete when the row is not trashed', () => {
      wrapper = mountActions({ model: liveModel, canForceDelete: true, canView: true });

      expect(wrapper.text()).not.toContain('trash.permanently_delete');
    });

    it('hides permanent delete when force-delete is not allowed', () => {
      wrapper = mountActions({ canForceDelete: false, canRestore: true });

      expect(wrapper.text()).not.toContain('trash.permanently_delete');
    });

    it('renders no overflow menu when there is nothing destructive to offer', () => {
      wrapper = mountActions({ canRestore: true, canForceDelete: false });

      expect(wrapper.find('[data-testid="row-actions-overflow"]').exists()).toBe(false);
    });

    it('opens the type-to-confirm dialog from the overflow menu', async () => {
      wrapper = mountActions({ canForceDelete: true });

      await wrapper.find('[data-testid="row-action-force-delete"]').trigger('click');

      expect(wrapper.findComponent({ name: 'ConfirmDangerousActionDialog' }).props('open')).toBe(true);
    });
  });

  describe('blocked permanent deletion', () => {
    const blockedModel = {
      id: 1,
      name: 'Test record',
      deleted_at: '2026-07-27T00:00:00.000000Z',
      force_delete_blocked_reason: 'Susieta narystės istorija.',
    } satisfies TestModel;

    it('replaces the action with the reason supplied by the server', () => {
      wrapper = mountActions({ model: blockedModel, canForceDelete: true });

      expect(wrapper.find('[data-testid="row-action-force-delete"]').exists()).toBe(false);
      expect(wrapper.find('[data-testid="row-action-force-delete-blocked"]').text())
        .toContain('Susieta narystės istorija.');
    });

    it('still opens the menu so the explanation is reachable', () => {
      wrapper = mountActions({ model: blockedModel, canForceDelete: true });

      expect(wrapper.find('[data-testid="row-actions-overflow"]').exists()).toBe(true);
    });
  });
});
