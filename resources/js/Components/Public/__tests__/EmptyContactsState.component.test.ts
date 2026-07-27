import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import EmptyContactsState from '../EmptyContactsState.vue';

describe('EmptyContactsState', () => {
  it('uses generic wording for institutions without public contacts', () => {
    const wrapper = mount(EmptyContactsState);

    expect(wrapper.text()).toContain('Šiuo metu kontaktų nėra');
    expect(wrapper.text()).toContain('Ši institucija šiuo metu neturi viešai skelbiamų kontaktų.');
    expect(wrapper.text()).not.toContain('studentų atstovų');
  });
});
