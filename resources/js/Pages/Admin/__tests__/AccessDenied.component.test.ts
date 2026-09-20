import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import AccessDenied from '@/Pages/Admin/AccessDenied.vue';

const base = {
  permission: null,
  action: null,
  resource: null,
  message: null,
};

function createWrapper(props: Partial<typeof base> = {}) {
  return mount(AccessDenied, {
    props: { ...base, ...props },
    global: {
      stubs: { Head: true, Link: { template: '<a><slot /></a>' } },
    },
  });
}

describe('AccessDenied', () => {
  it('names the missing permission when the denial is known', () => {
    const wrapper = createWrapper({ permission: 'news.update', action: 'Redaguoti', resource: 'naujienos' });

    expect(wrapper.find('[data-testid="forbidden-missing"]').text()).toBe('Redaguoti · naujienos');
    expect(wrapper.text()).toContain('news.update');
  });

  it('shows the thrown message instead when no permission is known', () => {
    const wrapper = createWrapper({ message: 'Form field does not belong to this form.' });

    expect(wrapper.find('[data-testid="forbidden-missing"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('Form field does not belong to this form.');
  });

  it('points the primary action at the roles page, where the missing permission can be understood', () => {
    const wrapper = createWrapper();

    expect(wrapper.find('a[href$="/mocked-route/profile.roles"]').exists()).toBe(true);
    expect(wrapper.find('a[href$="/mocked-route/profile"]').exists()).toBe(false);
  });

  it('links to the help pages in the current language', () => {
    const wrapper = createWrapper();

    expect(wrapper.find('a[href="/docs"]').exists()).toBe(true);
  });
});
