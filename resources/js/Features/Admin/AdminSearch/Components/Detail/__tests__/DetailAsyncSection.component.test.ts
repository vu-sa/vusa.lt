import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import DetailAsyncSection from '../DetailAsyncSection.vue';

describe('DetailAsyncSection', () => {
  it('shows skeleton loaders while fetching and no content', () => {
    const wrapper = mount(DetailAsyncSection, {
      props: {
        title: 'Async',
        isFetching: true,
        hasContent: false,
        emptyMessage: 'Empty',
      },
      slots: { default: '<p data-testid="slot">Content</p>' },
    });

    expect(wrapper.findAll('.animate-pulse').length).toBe(3);
    expect(wrapper.find('[data-testid="slot"]').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('Empty');
  });

  it('renders slot when content is present', () => {
    const wrapper = mount(DetailAsyncSection, {
      props: {
        title: 'Async',
        isFetching: false,
        hasContent: true,
        emptyMessage: 'Empty',
      },
      slots: { default: '<p data-testid="slot">Content</p>' },
    });

    expect(wrapper.find('[data-testid="slot"]').exists()).toBe(true);
  });

  it('shows empty message when not fetching and no content', () => {
    const wrapper = mount(DetailAsyncSection, {
      props: {
        title: 'Async',
        isFetching: false,
        hasContent: false,
        emptyMessage: 'Nothing here',
      },
    });

    expect(wrapper.text()).toContain('Nothing here');
  });

  it('renders the requested number of skeleton rows', () => {
    const wrapper = mount(DetailAsyncSection, {
      props: {
        title: 'Async',
        isFetching: true,
        hasContent: false,
        emptyMessage: 'Empty',
        skeletonCount: 5,
      },
    });

    expect(wrapper.findAll('.animate-pulse').length).toBe(5);
  });
});
