import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AgendaItemTextTabs from '@/Components/AgendaItems/AgendaItemTextTabs.vue';
import { commonStubs } from '@/tests/stubs';

function factory(props: Record<string, unknown> = {}) {
  return mount(AgendaItemTextTabs, {
    props,
    global: { stubs: { ...commonStubs } },
  });
}

describe('AgendaItemTextTabs', () => {
  it('renders a textarea when editable', () => {
    const wrapper = factory({ editable: true, description: 'Hi', studentPosition: '' });
    expect(wrapper.find('textarea').exists()).toBe(true);
  });

  it('renders read-only text when not editable', () => {
    const wrapper = factory({ editable: false, description: 'Some description', studentPosition: '' });
    expect(wrapper.find('textarea').exists()).toBe(false);
    expect(wrapper.text()).toContain('Some description');
  });

  it('shows an empty state when read-only with no content', () => {
    const wrapper = factory({ editable: false, description: '', studentPosition: '' });
    expect(wrapper.text()).toContain('Nenurodyta');
  });

  it('emits an update when the description textarea changes', async () => {
    const wrapper = factory({ editable: true, description: '', studentPosition: '' });
    await wrapper.find('textarea').setValue('New text');
    expect(wrapper.emitted('update:description')?.[0]).toEqual(['New text']);
  });
});
