import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import OrderedListInput from '../OrderedListInput.vue';

describe('OrderedListInput', () => {
  it('renders existing items with 1-based order indicators', () => {
    const wrapper = mount(OrderedListInput, {
      props: {
        modelValue: ['First point', 'Second point'],
      },
    });

    expect(wrapper.text()).toContain('1');
    expect(wrapper.text()).toContain('2');
    const inputs = wrapper.findAll('ol input');
    expect(inputs[0].element.value).toBe('First point');
    expect(inputs[1].element.value).toBe('Second point');
  });

  it('emits updated modelValue when item content changes', async () => {
    const wrapper = mount(OrderedListInput, {
      props: {
        modelValue: ['Item 1'],
      },
    });

    const input = wrapper.find('ol input');
    await input.setValue('Updated Item 1');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([['Updated Item 1']]);
  });

  it('supports textarea inputType when specified', () => {
    const wrapper = mount(OrderedListInput, {
      props: {
        modelValue: ['Textarea content'],
        inputType: 'textarea',
      },
    });

    const textarea = wrapper.find('ol textarea');
    expect(textarea.exists()).toBe(true);
    expect(textarea.element.value).toBe('Textarea content');
  });

  it('removes an item when the delete button is clicked', async () => {
    const wrapper = mount(OrderedListInput, {
      props: {
        modelValue: ['Item 1', 'Item 2'],
      },
    });

    const deleteButtons = wrapper.findAll('button[aria-label^="Šalinti"]');
    expect(deleteButtons).toHaveLength(2);

    await deleteButtons[0].trigger('click');
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([['Item 2']]);
  });

  it('adds a draft item on button click and clears the draft input', async () => {
    const wrapper = mount(OrderedListInput, {
      props: {
        modelValue: ['Item 1'],
        max: 3,
      },
    });

    const draftInput = wrapper.find('[data-testid="ordered-list-draft-input"]');
    await draftInput.setValue('New Item 2');

    const addButton = wrapper.find('[data-testid="ordered-list-add-draft"]');
    await addButton.trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([['Item 1', 'New Item 2']]);
    expect((draftInput.element as HTMLInputElement).value).toBe('');
  });

  it('hides the draft input bar when max is reached', () => {
    const wrapper = mount(OrderedListInput, {
      props: {
        modelValue: ['Item 1', 'Item 2', 'Item 3'],
        max: 3,
      },
    });

    expect(wrapper.find('[data-testid="ordered-list-draft-input"]').exists()).toBe(false);
  });
});
