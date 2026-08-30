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

  /**
   * The textarea stays in place and is locked instead of being swapped for a
   * paragraph — the swap shifted the whole page whenever the edit toggle flipped.
   */
  it('locks the textarea when not editable', () => {
    const wrapper = factory({ editable: false, description: 'Some description', studentPosition: '' });
    const textarea = wrapper.find('textarea');

    expect(textarea.attributes('readonly')).toBeDefined();
    expect((textarea.element as HTMLTextAreaElement).value).toBe('Some description');
  });

  it('shows an empty-state placeholder when read-only with no content', () => {
    const wrapper = factory({ editable: false, description: '', studentPosition: '' });
    expect(wrapper.find('textarea').attributes('placeholder')).toBe('Nenurodyta');
  });

  it('emits an update when the description textarea changes', async () => {
    const wrapper = factory({ editable: true, description: '', studentPosition: '' });
    await wrapper.find('textarea').setValue('New text');
    expect(wrapper.emitted('update:description')?.[0]).toEqual(['New text']);
  });
});
