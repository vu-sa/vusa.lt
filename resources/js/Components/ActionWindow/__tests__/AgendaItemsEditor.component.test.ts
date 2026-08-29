import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h, ref } from 'vue';

import AgendaItemsEditor from '@/Components/ActionWindow/AgendaItemsEditor.vue';

/**
 * The window's own agenda editor, deliberately much smaller than the meeting page's:
 * a question is a line of text, and the list has to behave like one on a phone.
 */
const mountEditor = (initial: string[] = ['']) => {
  const items = ref(initial);

  const wrapper = mount(defineComponent({
    setup: () => () => h(AgendaItemsEditor, {
      'modelValue': items.value,
      'onUpdate:modelValue': (value: string[]) => {
        items.value = value;
      },
    }),
  }));

  return { wrapper, items };
};

const inputs = (wrapper: ReturnType<typeof mount>) => wrapper.findAll('input');

describe('AgendaItemsEditor.vue', () => {
  it('starts with a single empty line', () => {
    const { wrapper } = mountEditor();

    expect(inputs(wrapper)).toHaveLength(1);
  });

  it('renders one input per existing question, numbered', () => {
    const { wrapper } = mountEditor(['Pirmas', 'Antras']);

    expect(inputs(wrapper)).toHaveLength(2);
    expect(wrapper.text()).toContain('1.');
    expect(wrapper.text()).toContain('2.');
  });

  it('typing updates the bound list', async () => {
    const { wrapper, items } = mountEditor();
    await inputs(wrapper)[0]!.setValue('Biudžetas');

    expect(items.value).toEqual(['Biudžetas']);
  });

  it('Enter opens the next line rather than submitting', async () => {
    const { wrapper, items } = mountEditor(['Pirmas']);
    await inputs(wrapper)[0]!.trigger('keydown.enter');

    expect(items.value).toEqual(['Pirmas', '']);
  });

  it('the add button appends a line', async () => {
    const { wrapper, items } = mountEditor(['Pirmas']);
    await wrapper.findAll('button').at(-1)!.trigger('click');

    expect(items.value).toEqual(['Pirmas', '']);
  });

  it('removing a line drops it', async () => {
    const { wrapper, items } = mountEditor(['Pirmas', 'Antras']);
    // Each row's own remove button, not the trailing "add another".
    await wrapper.findAll('button')[0]!.trigger('click');

    expect(items.value).toEqual(['Antras']);
  });

  it('never leaves the list empty, so there is always somewhere to type', async () => {
    const { wrapper, items } = mountEditor(['Vienintelis']);
    await wrapper.findAll('button')[0]!.trigger('click');

    expect(items.value).toEqual(['']);
  });

  it('backspace on an empty line removes it, the way list editors behave', async () => {
    const { wrapper, items } = mountEditor(['Pirmas', '']);
    await inputs(wrapper)[1]!.trigger('keydown.backspace');

    expect(items.value).toEqual(['Pirmas']);
  });

  it('backspace on the last remaining line leaves it alone', async () => {
    const { wrapper, items } = mountEditor(['']);
    await inputs(wrapper)[0]!.trigger('keydown.backspace');

    expect(items.value).toEqual(['']);
  });
});
