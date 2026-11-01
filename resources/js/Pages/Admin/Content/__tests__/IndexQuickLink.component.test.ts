import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

import IndexQuickLink from '@/Pages/Admin/Content/IndexQuickLink.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.mock('@vueuse/integrations/useSortable', () => ({
  useSortable: vi.fn(() => ({ option: vi.fn(), stop: vi.fn(), start: vi.fn() })),
}));

/** The collection shell is covered by its own suite; here it only hands over its slots and events. */
const CollectionPageStub = {
  name: 'CollectionPage',
  props: ['source', 'quickFilters', 'trash', 'keepParams', 'title', 'rowActions', 'defaultView', 'columns'],
  emits: ['quickFilter', 'rowAction'],
  template: `
    <div data-testid="collection-page">
      <div data-testid="actions"><slot name="actions" /></div>
      <div v-for="item in source.items.value" :key="item.id" data-testid="row"><slot name="row" :item="item" :actions="rowActions?.(item) ?? []" /></div>
      <div v-if="source.items.value.length === 0"><slot name="empty" /></div>
    </div>
  `,
};

const SheetFormStub = {
  name: 'SheetForm',
  props: ['open', 'disabled', 'dirty', 'processing'],
  emits: ['submit', 'update:open'],
  template: `
    <div v-if="open" data-testid="reorder-sheet">
      <slot />
      <button data-testid="save-order" :disabled="disabled" @click="$emit('submit')">save</button>
    </div>
  `,
};

const links = [
  { id: 1, text: 'Stipendijos', link: '/stipendijos', icon: null, order: 1, is_important: false, tenant_id: 10, lang: 'lt' },
  { id: 2, text: 'Bendrabučiai', link: '/bendrabuciai', icon: null, order: 2, is_important: true, tenant_id: 10, lang: 'lt' },
  { id: 3, text: 'Kontaktai', link: '/kontaktai', icon: null, order: 3, is_important: false, tenant_id: 10, lang: 'lt' },
];

const tenants = [
  { id: 10, shortname: 'VU SA', type: 'pagrindinis' },
  { id: 11, shortname: 'VU SA MIF', type: 'padalinys' },
];

function createWrapper(props: Record<string, unknown> = {}) {
  return mount(IndexQuickLink, {
    props: {
      quickLinks: links,
      tenant: { id: 10, shortname: 'VU SA' },
      tenants,
      reorderTenants: tenants,
      currentLang: 'lt',
      deletedCount: 0,
      ...props,
    },
    global: {
      stubs: {
        ...commonStubs,
        CollectionPage: CollectionPageStub,
        SheetForm: SheetFormStub,
        CollectionConfirmAction: true,
        CollectionRowActions: true,
        NativeSelect: {
          name: 'NativeSelect',
          props: ['modelValue', 'options'],
          emits: ['update:modelValue'],
          template: '<div data-testid="tenant-select" />',
        },
      },
    },
  });
}

describe('IndexQuickLink.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  beforeEach(() => {
    vi.clearAllMocks();
    sessionStorage.clear();
    localStorage.clear();
    window.history.replaceState({}, '', '/mano/quickLinks');
    vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can: { create: { quickLink: true }, forceDelete: { quickLink: false } } } }));
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  describe('scope', () => {
    it('shows all languages and tenants together with standard filters and a table by default', () => {
      wrapper = createWrapper({ quickLinks: [...links,
        { ...links[0], id: 4, text: 'English', lang: 'en' },
        { ...links[0], id: 5, text: 'MIF', tenant_id: 11 },
      ] });
      const collection = wrapper.findComponent({ name: 'CollectionPage' });
      expect(collection.props('defaultView')).toBe('table');
      expect(wrapper.findAll('[data-testid="row"]')).toHaveLength(5);
      expect(collection.props('source').facets.value.map((facet: { field: string }) => facet.field)).toEqual(['tenant_id', 'language']);
      expect(wrapper.findComponent({ name: 'NativeSelect' }).exists()).toBe(false);
    });

    it('filters languages locally without reloading the page', async () => {
      wrapper = createWrapper({ quickLinks: [...links, { ...links[0], id: 4, text: 'English', lang: 'en' }] });
      wrapper.findComponent({ name: 'CollectionPage' }).vm.$emit('quickFilter', 'en');
      await nextTick();
      expect(wrapper.findAll('[data-testid="row"]')).toHaveLength(1);
      expect(wrapper.text()).toContain('English');
      expect(router.get).not.toHaveBeenCalled();
    });

    it('combines tenant and language filters and keeps them across the trash toggle', async () => {
      wrapper = createWrapper({ quickLinks: [...links,
        { ...links[0], id: 4, text: 'English', lang: 'en' },
        { ...links[0], id: 5, text: 'MIF English', tenant_id: 11, lang: 'en' },
      ] });
      const collection = wrapper.findComponent({ name: 'CollectionPage' });
      collection.props('source').setFilter('tenant_id', ['11']);
      collection.props('source').setFilter('language', ['en']);
      await nextTick();
      expect(wrapper.findAll('[data-testid="row"]')).toHaveLength(1);
      expect(wrapper.text()).toContain('MIF English');
      expect(collection.props('keepParams')).toEqual(['tenant_id', 'language']);
    });
  });

  it('preserves the default edit and delete actions independently of creation ability', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can: { create: { quickLink: false } } } }));
    wrapper = createWrapper();
    const rowActions = wrapper.findComponent({ name: 'CollectionPage' }).props('rowActions');
    expect(rowActions(links[0])).toEqual([
      expect.objectContaining({ key: 'edit:1', role: 'edit', label: 'Redaguoti' }),
      expect.objectContaining({ key: 'delete:1:Stipendijos', destructive: true }),
      expect.objectContaining({ key: 'reorder', label: 'Keisti tvarką', menuOnly: true }),
    ]);
  });

  it.each([false, true])('preserves default restoration and the force-delete gate (%s)', (forceDelete) => {
    window.history.replaceState({}, '', '/mano/quickLinks?showDeleted=true');
    vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can: {
      create: { quickLink: false },
      forceDelete: { quickLink: forceDelete },
    } } }));
    wrapper = createWrapper();
    const rowActions = wrapper.findComponent({ name: 'CollectionPage' }).props('rowActions');
    const actions = rowActions(links[0]);
    expect(actions.map((action: { key: string }) => action.key.split(':')[0])).toEqual([
      'restore',
      ...(forceDelete ? ['forceDelete'] : []),
    ]);
    expect(actions[0].primary).toBe(!forceDelete);
  });

  describe('create', () => {
    it('offers the create action outside the trash', () => {
      wrapper = createWrapper();

      expect(wrapper.find('[data-testid="inline-create-button"]').attributes('href')).toBe('/mocked-route/quickLinks.create?tenant=10&lang=lt');
    });

    it('hides create and reorder in the trash', () => {
      window.history.replaceState({}, '', '/mano/quickLinks?showDeleted=true');
      wrapper = createWrapper();

      expect(wrapper.find('[data-testid="inline-create-button"]').exists()).toBe(false);
      expect(wrapper.find('[data-testid="reorder-button"]').exists()).toBe(false);
    });
  });

  describe('reorder mode', () => {
    it('hides reordering without update permission', () => {
      wrapper = createWrapper({ reorderTenants: [] });

      expect(wrapper.find('[data-testid="reorder-button"]').exists()).toBe(false);
      const rowActions = wrapper.findComponent({ name: 'CollectionPage' }).props('rowActions');
      expect(rowActions(links[0]).some((action: { key: string }) => action.key === 'reorder')).toBe(false);
    });

    it('opens with the saved order and cannot save until something moves', async () => {
      wrapper = createWrapper({ quickLinks: [links[2], links[0], links[1]] });

      await wrapper.find('[data-testid="reorder-button"]').trigger('click');

      const items = wrapper.findAll('[data-testid="reorder-item"]').map(item => item.text());
      expect(items[0]).toContain('Stipendijos');
      expect(items[2]).toContain('Kontaktai');
      expect(wrapper.find('[data-testid="save-order"]').attributes('disabled')).toBeDefined();
    });

    it('moves links with the arrow buttons and saves the new order for this tenant and language', async () => {
      wrapper = createWrapper();

      await wrapper.find('[data-testid="reorder-button"]').trigger('click');
      await wrapper.findAll('[data-testid="move-up"]')[2].trigger('click');
      await wrapper.find('[data-testid="save-order"]').trigger('click');

      expect(router.post).toHaveBeenCalledWith(
        '/mocked-route/quickLinks.update-order',
        {
          orderList: [
            { id: 1, order: 1 },
            { id: 3, order: 2 },
            { id: 2, order: 3 },
          ],
          tenant_id: 10,
          lang: 'lt',
        },
        expect.objectContaining({ preserveScroll: true }),
      );
    });

    it('opens a row’s complete tenant and language group despite table filters', async () => {
      const english = [
        { ...links[0], id: 4, text: 'English first', tenant_id: 11, lang: 'en', order: 1 },
        { ...links[1], id: 5, text: 'English second', tenant_id: 11, lang: 'en', order: 2 },
      ];
      wrapper = createWrapper({ quickLinks: [...links, ...english] });
      const collection = wrapper.findComponent({ name: 'CollectionPage' });
      collection.props('source').search('English first');
      collection.vm.$emit('rowAction', english[0], 'reorder');
      await nextTick();
      expect(wrapper.findAll('[data-testid="reorder-item"]')).toHaveLength(2);
      const selects = wrapper.findAllComponents({ name: 'NativeSelect' });
      expect(selects[0].props('modelValue')).toBe(11);
      expect(selects[1].props('modelValue')).toBe('en');
      await wrapper.findAll('[data-testid="move-up"]')[1].trigger('click');
      await wrapper.find('[data-testid="save-order"]').trigger('click');
      expect(router.post).toHaveBeenCalledWith('/mocked-route/quickLinks.update-order', {
        orderList: [{ id: 5, order: 1 }, { id: 4, order: 2 }], tenant_id: 11, lang: 'en',
      }, expect.any(Object));
    });

    it('prefills ordering from filters and switches groups inside the sheet', async () => {
      wrapper = createWrapper({ quickLinks: [...links, { ...links[0], id: 4, text: 'English', tenant_id: 11, lang: 'en' }] });
      const source = wrapper.findComponent({ name: 'CollectionPage' }).props('source');
      source.setFilter('tenant_id', ['11']);
      source.setFilter('language', ['en']);
      await wrapper.find('[data-testid="reorder-button"]').trigger('click');
      expect(wrapper.findAll('[data-testid="reorder-item"]')).toHaveLength(1);
      const selects = wrapper.findAllComponents({ name: 'NativeSelect' });
      selects[0].vm.$emit('update:modelValue', 10);
      selects[1].vm.$emit('update:modelValue', 'lt');
      await nextTick();
      expect(wrapper.findAll('[data-testid="reorder-item"]')).toHaveLength(3);
      expect(wrapper.find('[data-testid="save-order"]').attributes('disabled')).toBeDefined();
    });

    it('disables moving past either end', async () => {
      wrapper = createWrapper();

      await wrapper.find('[data-testid="reorder-button"]').trigger('click');

      expect(wrapper.findAll('[data-testid="move-up"]')[0].attributes('disabled')).toBeDefined();
      expect(wrapper.findAll('[data-testid="move-down"]')[2].attributes('disabled')).toBeDefined();
    });
  });

  it('shows a teaching empty state with a create action when there are no links', () => {
    wrapper = createWrapper({ quickLinks: [] });

    expect(wrapper.text()).toContain('Dar nėra greitųjų nuorodų');
    expect(wrapper.text()).toContain('Sukurti pirmą nuorodą');
  });
});
