import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import DetailSection from '../DetailSection.vue';

describe('DetailSection', () => {
  it('renders the title and slot content', () => {
    const wrapper = mount(DetailSection, {
      props: { title: 'Section Title' },
      slots: { default: '<p data-testid="slot">Slot content</p>' },
    });

    expect(wrapper.text()).toContain('Section Title');
    expect(wrapper.find('[data-testid="slot"]').exists()).toBe(true);
  });
});
