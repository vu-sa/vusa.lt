import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import IndexProblem from '../IndexProblem.vue';

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

    expect(actions.find(action => action.key === 'open')).toMatchObject({ primary: true, href: route('problems.show', 'p1') });
    expect(actions.find(action => action.key === 'edit')).toMatchObject({ menuOnly: true, href: route('problems.edit', 'p1') });
    wrapper.unmount();
  });
});
