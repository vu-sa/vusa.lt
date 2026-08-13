import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import { ShowPageGrid } from '..';

describe('ShowPageGrid', () => {
  it('renders both columns when a sidebar is supplied', () => {
    const wrapper = mount(ShowPageGrid, {
      slots: {
        main: '<p class="main-content">main</p>',
        sidebar: '<p class="sidebar-content">side</p>',
      },
    });

    expect(wrapper.find('.main-content').exists()).toBe(true);
    expect(wrapper.find('.sidebar-content').exists()).toBe(true);
  });

  it('goes two-column and constrains the main column only when a sidebar exists', () => {
    const wrapper = mount(ShowPageGrid, {
      slots: {
        main: '<p class="main-content">main</p>',
        sidebar: '<p class="sidebar-content">side</p>',
      },
    });

    expect(wrapper.classes()).toContain('xl:grid-cols-3');
    expect(wrapper.find('[data-slot="show-page-grid"] > div').classes()).toContain('xl:col-span-2');
  });

  it('lets main span full width when there is no sidebar', () => {
    const wrapper = mount(ShowPageGrid, {
      slots: { main: '<p class="main-content">main</p>' },
    });

    expect(wrapper.classes()).not.toContain('xl:grid-cols-3');
    expect(wrapper.find('[data-slot="show-page-grid"] > div').classes()).not.toContain('xl:col-span-2');
  });

  it('does not render an empty sidebar column when the slot is absent', () => {
    const wrapper = mount(ShowPageGrid, {
      slots: { main: '<p class="main-content">main</p>' },
    });

    expect(wrapper.findAll('[data-slot="show-page-grid"] > div')).toHaveLength(1);
  });
});
