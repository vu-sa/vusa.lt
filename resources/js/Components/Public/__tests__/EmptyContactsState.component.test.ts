import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import EmptyContactsState from '../EmptyContactsState.vue';

describe('EmptyContactsState', () => {
  it('shows the registration link only when a form is available', async () => {
    const wrapper = mount(EmptyContactsState);
    expect(wrapper.find('a').exists()).toBe(false);

    await wrapper.setProps({ studentRepFormInfo: {
      formPath: '/lt/registracija',
      institutionId: 'institution-1',
      institutionName: 'Studijų komitetas',
    } });

    expect(wrapper.get('a').attributes('href')).toBe('/lt/registracija?institution=institution-1');
  });
});
