import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import SectionBlockToolbar from '../SectionBlockToolbar.vue';
import type { ContentPart } from '../../Types';

const stubs = {
  RCBlockToolbarShell: {
    props: ['content', 'blockKey', 'reference', 'canMoveUp', 'canMoveDown', 'canDelete'],
    emits: ['move-up', 'move-down', 'delete', 'open-form'],
    template: '<div class="shell-stub"><slot /></div>',
  },
  RCPresentationPicker: {
    props: ['modelValue', 'plainPadding'],
    emits: ['update:modelValue', 'update:plainPadding'],
    template: '<div class="presentation-picker-stub" />',
  },
  Select: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)"><slot /></select>',
  },
  SelectTrigger: { template: '<div><slot /></div>' },
  SelectValue: { template: '<div />' },
  SelectContent: { template: '<slot />' },
  SelectItem: { props: ['value'], template: '<option :value="value"><slot /></option>' },
};

function makeContent(options: Record<string, unknown> = {}): ContentPart {
  return { type: 'section', json_content: {}, options };
}

function mountToolbar(content: ContentPart) {
  return mount(SectionBlockToolbar, {
    props: {
      content,
      blockKey: 'sec-1',
      canMoveUp: true,
      canMoveDown: true,
      canDelete: true,
    },
    global: { stubs },
  });
}

describe('SectionBlockToolbar', () => {
  it('shows the presentation picker (no width picker — section is locked to full)', () => {
    const wrapper = mountToolbar(makeContent());
    expect(wrapper.find('.presentation-picker-stub').exists()).toBe(true);
  });

  it('changing the heading-level select emits update:content with the new level', async () => {
    const wrapper = mountToolbar(makeContent({ headingLevel: 2 }));
    const [headingSelect] = wrapper.findAll('select');
    await headingSelect!.setValue('3');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as ContentPart).options?.headingLevel).toBe(3);
  });

  it('changing the alignment select emits update:content, preserving other options', async () => {
    const wrapper = mountToolbar(makeContent({ headingLevel: 2, showSeparator: false }));
    const [, alignSelect] = wrapper.findAll('select');
    await alignSelect!.setValue('start');

    const emitted = wrapper.emitted('update:content');
    const patched = emitted!.at(-1)![0] as ContentPart;
    expect(patched.options?.align).toBe('start');
    expect(patched.options?.headingLevel).toBe(2);
    expect(patched.options?.showSeparator).toBe(false);
  });

  it('toggling the separator switch emits update:content', async () => {
    const wrapper = mountToolbar(makeContent());
    await wrapper.find('button[role="switch"]').trigger('click');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as ContentPart).options?.showSeparator).toBe(false);
  });
});
