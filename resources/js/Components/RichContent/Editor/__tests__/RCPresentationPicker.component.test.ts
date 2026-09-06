import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';

import RCPresentationPicker from '../RCPresentationPicker.vue';
import { SECTION_PRESENTATION_DISABLED } from '../sectionPresentation';

describe('RCPresentationPicker', () => {
  it('offers only automatic and plain presentations', () => {
    const wrapper = mount(RCPresentationPicker);

    expect(wrapper.text()).toContain('rich-content.presentation_auto');
    expect(wrapper.text()).toContain('rich-content.presentation_plain');
    expect(wrapper.text()).not.toContain('rich-content.presentation_emphasis');
  });

  it('reveals three padding choices only for a plain section', async () => {
    const wrapper = mount(RCPresentationPicker, { props: { modelValue: 'auto' } });

    expect(wrapper.text()).not.toContain('rich-content.plain_padding_compact');

    await wrapper.setProps({ modelValue: 'plain' });

    expect(wrapper.text()).toContain('rich-content.plain_padding_none');
    expect(wrapper.text()).toContain('rich-content.plain_padding_compact');
    expect(wrapper.text()).toContain('rich-content.plain_padding_default');
  });

  it('emits the selected plain padding', async () => {
    const wrapper = mount(RCPresentationPicker, { props: { modelValue: 'plain' } });
    const compact = wrapper.findAll('button').find(button => button.text().includes('rich-content.plain_padding_compact'));

    await compact!.trigger('click');

    expect(wrapper.emitted('update:plainPadding')).toEqual([['compact']]);
  });

  it('shows default padding as selected when plain has no explicit padding value', () => {
    const wrapper = mount(RCPresentationPicker, { props: { modelValue: 'plain' } });
    const defaultPadding = wrapper.findAll('button').find(button => button.text().includes('rich-content.plain_padding_default'));

    expect(defaultPadding!.classes()).toContain('border-vusa-red');
  });

  it('disables presentation choices when a containing section owns the spacing', async () => {
    const wrapper = mount(RCPresentationPicker, {
      props: { modelValue: 'plain' },
      global: { provide: { [SECTION_PRESENTATION_DISABLED]: ref(true) } },
    });
    const buttons = wrapper.findAll('button');

    await buttons[0]!.trigger('click');

    expect(buttons.every(button => button.attributes('disabled') !== undefined)).toBe(true);
    expect(wrapper.text()).toContain('rich-content.section_padding_inherited');
    expect(wrapper.emitted('update:modelValue')).toBeUndefined();
  });
});
