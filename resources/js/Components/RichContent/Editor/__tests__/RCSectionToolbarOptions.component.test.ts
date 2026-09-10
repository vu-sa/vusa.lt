import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';

import RCSectionToolbarOptions from '../RCSectionToolbarOptions.vue';

import type { SectionOptions } from '@/Types/contentParts';

const stubs = {
  RCSectionOptionsFields: {
    props: ['modelValue', 'presentationDisabled'],
    emits: ['update:modelValue'],
    template: '<div class="section-options-fields-stub" />',
  },
  RCPresentationPicker: {
    props: ['modelValue', 'disabled'],
    template: '<div class="presentation-picker-stub" :data-disabled="disabled" />',
  },
};

describe('RCSectionToolbarOptions', () => {
  it('renders "Add section" button when options has no title, subtitle, or eyebrow', () => {
    const options = ref<SectionOptions>({});
    const wrapper = mount(RCSectionToolbarOptions, {
      props: {
        'modelValue': options.value,
        'onUpdate:modelValue': (val: SectionOptions) => { options.value = val; },
      },
      global: { stubs },
    });

    expect(wrapper.find('[data-rc-toolbar-add-section]').exists()).toBe(true);
    expect(wrapper.find('[data-rc-toolbar-section-fields]').exists()).toBe(false);
  });

  it('renders section fields when options has a title', () => {
    const options = ref<SectionOptions>({ title: 'Mano sekcija' });
    const wrapper = mount(RCSectionToolbarOptions, {
      props: {
        'modelValue': options.value,
        'onUpdate:modelValue': (val: SectionOptions) => { options.value = val; },
      },
      global: { stubs },
    });

    expect(wrapper.find('[data-rc-toolbar-add-section]').exists()).toBe(false);
    expect(wrapper.find('[data-rc-toolbar-section-fields]').exists()).toBe(true);
  });

  it('clicking "Add section" activates section fields', async () => {
    const options = ref<SectionOptions>({});
    const wrapper = mount(RCSectionToolbarOptions, {
      props: {
        'modelValue': options.value,
        'onUpdate:modelValue': (val: SectionOptions) => { options.value = val; },
      },
      global: { stubs },
    });

    await wrapper.get('[data-rc-toolbar-add-section]').trigger('click');

    expect(wrapper.find('[data-rc-toolbar-section-fields]').exists()).toBe(true);
    expect(wrapper.find('[data-rc-toolbar-add-section]').exists()).toBe(false);
  });

  it('clicking remove section clears title, subtitle, eyebrow and reverts to "Add section" button', async () => {
    const options = ref<SectionOptions>({ title: 'Pavadinimas', subtitle: 'Paantraštė', eyebrow: 'Kicker' });
    const wrapper = mount(RCSectionToolbarOptions, {
      props: {
        'modelValue': options.value,
        'onUpdate:modelValue': (val: SectionOptions) => { options.value = val; },
      },
      global: { stubs },
    });

    expect(wrapper.find('[data-rc-toolbar-section-fields]').exists()).toBe(true);

    await wrapper.get('[data-rc-toolbar-remove-section]').trigger('click');

    const emitted = wrapper.emitted('update:modelValue');
    expect(emitted).toBeTruthy();
    const updated = emitted!.at(-1)![0] as SectionOptions;
    expect(updated.title).toBe('');
    expect(updated.subtitle).toBe('');
    expect(updated.eyebrow).toBe('');

    // Re-render with cleared props
    await wrapper.setProps({ modelValue: updated });
    expect(wrapper.find('[data-rc-toolbar-add-section]').exists()).toBe(true);
    expect(wrapper.find('[data-rc-toolbar-section-fields]').exists()).toBe(false);
  });
});
