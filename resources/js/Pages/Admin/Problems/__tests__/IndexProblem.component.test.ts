import { mount } from '@vue/test-utils';
import { ArrowUpRight } from '@lucide/vue';
import { describe, expect, it } from 'vitest';

import IndexProblem from '../IndexProblem.vue';

import type { CollectionColumn } from '@/Components/Collection/types';
import type { CollectionSource } from '@/Composables/useCollectionSource';
import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';
import { commonStubs } from '@/tests/stubs';

describe('problem collection guidance', () => {
  it('links internal support requests to the support request list', () => {
    const wrapper = mount(IndexProblem, {
      props: {
        data: [],
        meta: { total: 0, current_page: 1, per_page: 50, last_page: 1, from: 0, to: 0 },
        categories: [],
        institutions: [],
      },
      global: {
        stubs: {
          ...commonStubs,
          CollectionPage: { template: '<div><slot name="lead" /></div>' },
        },
      },
    });

    expect(wrapper.text()).toContain('problems.internal_support.guidance');
    expect(wrapper.get('a').text()).toBe('problems.internal_support.link');
    expect(wrapper.get('a').attributes('href')).toBe(route('mySupportRequests.index'));
  });

  it('hides optional dates and offers category and tenant filters without institutions', () => {
    const collection = { name: 'CollectionPage', props: ['columns', 'source', 'defaultView'], template: '<div />' };
    const wrapper = mount(IndexProblem, {
      props: {
        data: [],
        meta: { total: 0, current_page: 1, per_page: 50, last_page: 1, from: 0, to: 0 },
        categories: [], institutions: [],
      },
      global: { stubs: { ...commonStubs, CollectionPage: collection } },
    });
    const page = wrapper.findComponent(collection);
    expect(page.props('defaultView')).toBe('table');
    const columns = page.props('columns') as CollectionColumn[];
    expect(columns.slice(0, 2).map(column => column.key)).toEqual(['title', 'tenant']);
    expect(columns.find(column => column.key === 'occurred_at')?.defaultHidden).toBe(true);
    expect(columns.find(column => column.key === 'resolved_at')?.defaultHidden).toBe(true);
    const source = page.props('source') as CollectionSource<unknown>;
    const fields = source.facets.value.map(facet => facet.field);
    expect(fields).toContain('category');
    expect(fields).toContain('status');
    expect(fields).not.toContain('institution');
    wrapper.unmount();
  });

  it('opens the record first and keeps editing in the menu', () => {
    const collection = { name: 'CollectionPage', props: ['rowActions'], template: '<div />' };
    const wrapper = mount(IndexProblem, {
      props: {
        data: [],
        meta: { total: 0, current_page: 1, per_page: 50, last_page: 1, from: 0, to: 0 },
        categories: [], institutions: [],
      },
      global: { stubs: { ...commonStubs, CollectionPage: collection } },
    });
    const actions: AdminRecordAction[] = wrapper.findComponent(collection).props('rowActions')({ id: 'p1', title: 'Problema', can_update: true });

    expect(actions.find(action => action.key === 'open')).toMatchObject({ primary: true, icon: ArrowUpRight, href: route('problems.show', 'p1') });
    expect(actions.find(action => action.key === 'edit')).toMatchObject({ menuOnly: true, href: route('problems.edit', 'p1') });
    wrapper.unmount();
  });
});
