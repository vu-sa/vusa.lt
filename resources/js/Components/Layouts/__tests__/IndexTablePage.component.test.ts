import { describe, it, expect, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent } from 'vue';
import type { ColumnDef } from '@tanstack/vue-table';

import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { commonStubs } from '@/tests/stubs';

interface TestRow {
  id: number;
  name: string;
}

const columns: ColumnDef<TestRow, any>[] = [{ accessorKey: 'name', header: 'Name' }];

describe('IndexTablePage', () => {
  let wrapper: ReturnType<typeof mount>;

  const mountPage = (props: Record<string, unknown> = {}) => mount(IndexTablePage, {
    props: {
      modelName: 'tests',
      columns: columns as ColumnDef<unknown, any>[],
      data: [],
      totalCount: 0,
      ...props,
    },
    global: {
      stubs: {
        ...commonStubs,
        AdminContentPage: defineComponent({ name: 'AdminContentPage', template: '<div><slot /></div>' }),
        ServerDataTable: defineComponent({
          name: 'ServerDataTable',
          // Mirrors the real component's `v-if="$slots.filters"` filter-button gate.
          template: '<div><slot name="actions" /><div v-if="$slots.filters" data-testid="filters-slot-forwarded"><slot name="filters" /></div></div>',
        }),
        Link: { template: '<a :href="href"><slot /></a>', props: ['href'] },
      },
    },
  });

  afterEach(() => {
    wrapper?.unmount();
    vi.clearAllMocks();
  });

  it('renders no overflow trigger when the page has only its primary action', () => {
    wrapper = mountPage({ canCreate: true, createRoute: '/mano/tests/create' });

    expect(wrapper.find('[data-testid="page-secondary-actions"]').exists()).toBe(false);
  });

  // A row of equally weighted header buttons hides which one the page is for,
  // so everything that is not "create" collapses behind one trigger.
  it('collects secondary actions into a single overflow menu', () => {
    wrapper = mountPage({
      canCreate: true,
      createRoute: '/mano/tests/create',
      secondaryActions: [
        { label: 'Merge duties', href: '/mano/duties/merge' },
        { label: 'Duty wizard', href: '/mano/duties/wizard' },
      ],
    });

    expect(wrapper.find('[data-testid="page-secondary-actions"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Merge duties');
    expect(wrapper.text()).toContain('Duty wizard');
  });

  it('renders navigational secondary actions as links, keeping middle-click usable', () => {
    wrapper = mountPage({
      secondaryActions: [{ label: 'Merge duties', href: '/mano/duties/merge' }],
    });

    const link = wrapper.findAll('a').find(anchor => anchor.text().includes('Merge duties'));

    expect(link?.attributes('href')).toBe('/mano/duties/merge');
  });

  it('calls onSelect for actions that are not navigations', async () => {
    const onSelect = vi.fn();
    wrapper = mountPage({ secondaryActions: [{ label: 'Recalculate', onSelect }] });

    const item = wrapper.findAll('button').find(button => button.text().includes('Recalculate'));
    await item?.trigger('click');

    expect(onSelect).toHaveBeenCalledOnce();
  });

  // ServerDataTable shows its filter-toggle button whenever it receives a
  // #filters slot at all, even an empty one, so an unconditional pass-through
  // would show the button on pages with no filters to offer.
  it('does not forward a filters slot when the page defines none', () => {
    wrapper = mountPage();

    expect(wrapper.find('[data-testid="filters-slot-forwarded"]').exists()).toBe(false);
  });

  it('forwards the filters slot when the page defines one', () => {
    wrapper = mount(IndexTablePage, {
      props: {
        modelName: 'tests',
        columns: columns as ColumnDef<unknown, any>[],
        data: [],
        totalCount: 0,
      },
      slots: {
        filters: '<div data-testid="page-filter-control" />',
      },
      global: {
        stubs: {
          ...commonStubs,
          AdminContentPage: defineComponent({ name: 'AdminContentPage', template: '<div><slot /></div>' }),
          ServerDataTable: defineComponent({
            name: 'ServerDataTable',
            template: '<div><slot name="actions" /><div v-if="$slots.filters" data-testid="filters-slot-forwarded"><slot name="filters" /></div></div>',
          }),
          Link: { template: '<a :href="href"><slot /></a>', props: ['href'] },
        },
      },
    });

    expect(wrapper.find('[data-testid="filters-slot-forwarded"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="page-filter-control"]').exists()).toBe(true);
  });
});
