import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import ResourceManagers from '../ResourceManagers.vue';

describe('ResourceManagers', () => {
  it('keeps a contact without an email readable without an empty mail link', () => {
    const wrapper = mount(ResourceManagers, {
      props: { managers: [{ id: 'u1', name: 'Ona', email: null }] },
    });

    expect(wrapper.text()).toContain('Ona');
    expect(wrapper.find('a').exists()).toBe(false);
  });

  it('shows an explicit vacancy on a resource with no current contacts', () => {
    const wrapper = mount(ResourceManagers, { props: { managers: [] } });

    expect(wrapper.text()).toContain('reservations.resource.no_managers');
  });
});
