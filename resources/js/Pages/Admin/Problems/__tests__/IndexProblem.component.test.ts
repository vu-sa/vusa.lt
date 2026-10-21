import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import IndexProblem from '../IndexProblem.vue';

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
});
