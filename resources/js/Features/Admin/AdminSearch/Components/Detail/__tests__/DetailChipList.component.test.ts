import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import DetailChipList from '../DetailChipList.vue';

describe('DetailChipList', () => {
  it('renders items as spans by default', () => {
    const wrapper = mount(DetailChipList, {
      props: {
        items: [
          { id: '1', name: 'Alice' },
          { id: '2', name: 'Bob' },
        ],
      },
    });

    expect(wrapper.text()).toContain('Alice');
    expect(wrapper.text()).toContain('Bob');
    expect(wrapper.find('a').exists()).toBe(false);
  });

  it('renders linked chips when getHref returns a value', () => {
    const wrapper = mount(DetailChipList, {
      props: {
        items: [{ id: '1', name: 'Alice' }],
        getHref: (item: { id: string; name: string }) => `/user/${item.id}`,
      },
    });

    const link = wrapper.find('a');
    expect(link.exists()).toBe(true);
    expect(link.attributes('href')).toBe('/user/1');
  });

  it('collapses items beyond collapseAt', () => {
    const wrapper = mount(DetailChipList, {
      props: {
        items: [
          { id: '1', name: 'Alice' },
          { id: '2', name: 'Bob' },
          { id: '3', name: 'Charlie' },
        ],
        collapseAt: 2,
      },
    });

    expect(wrapper.text()).toContain('+1');
  });

  it('renders empty message when items are empty', () => {
    const wrapper = mount(DetailChipList, {
      props: {
        items: [],
        emptyMessage: 'No items',
      },
    });

    expect(wrapper.text()).toContain('No items');
  });

  it('renders avatars when enabled and avatar data is present', () => {
    const wrapper = mount(DetailChipList, {
      props: {
        items: [{ id: '1', name: 'Alice', initials: 'A' }],
        avatar: true,
      },
    });

    expect(wrapper.find('[class*="size-5"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('A');
  });
});
