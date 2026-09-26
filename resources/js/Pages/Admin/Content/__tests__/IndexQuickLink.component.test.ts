import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

import IndexQuickLink from '@/Pages/Admin/Content/IndexQuickLink.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.mock('@vueuse/integrations/useSortable', () => ({
  useSortable: vi.fn(),
}));

/** The collection shell is covered by its own suite; here it only hands over its slots and events. */
const CollectionPageStub = {
  name: 'CollectionPage',
  props: ['source', 'quickFilters', 'trash', 'keepParams', 'title'],
  emits: ['quickFilter'],
  template: `
    <div data-testid="collection-page">
      <div data-testid="actions"><slot name="actions" /></div>
      <div v-for="item in source.items.value" :key="item.id" data-testid="row"><slot name="row" :item="item" /></div>
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
  { id: 1, text: 'Stipendijos', link: '/stipendijos', icon: null, order: 1, is_important: false },
  { id: 2, text: 'Bendrabučiai', link: '/bendrabuciai', icon: null, order: 2, is_important: true },
  { id: 3, text: 'Kontaktai', link: '/kontaktai', icon: null, order: 3, is_important: false },
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
        SingleSelect: {
          name: 'SingleSelect',
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
    window.history.replaceState({}, '', '/mano/quickLinks');
    vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { can: { create: { quickLink: true }, forceDelete: { quickLink: false } } } }));
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  describe('scope', () => {
    it('shows the current language as the active quick filter', () => {
      wrapper = createWrapper({ currentLang: 'en' });

      const filters = wrapper.findComponent({ name: 'CollectionPage' }).props('quickFilters');
      expect(filters).toEqual([
        { id: 'lt', label: 'LT', active: false },
        { id: 'en', label: 'EN', active: true },
      ]);
    });

    it('reloads the list for another language, keeping the tenant', async () => {
      wrapper = createWrapper();

      wrapper.findComponent({ name: 'CollectionPage' }).vm.$emit('quickFilter', 'en');
      await nextTick();

      expect(router.get).toHaveBeenCalledWith('/mocked-route/quickLinks.index', { tenant: 10, lang: 'en' }, { preserveState: false });
    });

    it('reloads the list for another tenant and stays in the trash when there', async () => {
      window.history.replaceState({}, '', '/mano/quickLinks?showDeleted=true');
      wrapper = createWrapper();

      wrapper.findComponent({ name: 'SingleSelect' }).vm.$emit('update:modelValue', tenants[1]);
      await nextTick();

      expect(router.get).toHaveBeenCalledWith(
        '/mocked-route/quickLinks.index',
        { tenant: 11, lang: 'lt', showDeleted: true },
        { preserveState: false },
      );
    });

    it('keeps tenant and language across the trash toggle', () => {
      wrapper = createWrapper();

      expect(wrapper.findComponent({ name: 'CollectionPage' }).props('keepParams')).toEqual(['tenant', 'lang']);
    });
  });

  describe('create', () => {
    it('offers the create action outside the trash', () => {
      wrapper = createWrapper();

      expect(wrapper.find('[data-testid="inline-create-button"]').attributes('href')).toBe('/mocked-route/quickLinks.create');
    });

    it('hides create and reorder in the trash', () => {
      window.history.replaceState({}, '', '/mano/quickLinks?showDeleted=true');
      wrapper = createWrapper();

      expect(wrapper.find('[data-testid="inline-create-button"]').exists()).toBe(false);
      expect(wrapper.find('[data-testid="reorder-button"]').exists()).toBe(false);
    });
  });

  describe('reorder mode', () => {
    it('is not offered for a single link', () => {
      wrapper = createWrapper({ quickLinks: [links[0]] });

      expect(wrapper.find('[data-testid="reorder-button"]').exists()).toBe(false);
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
