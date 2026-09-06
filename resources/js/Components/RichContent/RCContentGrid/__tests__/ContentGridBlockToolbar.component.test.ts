import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import ContentGridBlockToolbar from '../ContentGridBlockToolbar.vue';
import type { ContentPart } from '../../Types';

import { Select } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';

const stubs = {
  RCBlockToolbarShell: {
    props: ['content', 'blockKey', 'reference', 'canMoveUp', 'canMoveDown', 'canDelete'],
    emits: ['move-up', 'move-down', 'delete', 'open-form'],
    template: '<div class="shell-stub"><slot /></div>',
  },
  RCSectionToolbarOptions: {
    props: ['modelValue', 'presentationDisabled'],
    emits: ['update:modelValue'],
    template: '<div class="section-toolbar-stub" />',
  },
  RCWidthPicker: {
    props: ['modelValue', 'allowedWidths'],
    emits: ['update:modelValue'],
    template: '<div class="width-picker-stub" />',
  },
};

function makeContent(options: Record<string, unknown> = {}): ContentPart {
  return {
    type: 'content-grid',
    json_content: [{ columns: [{ width: 'col-span-12', content: { type: 'tiptap', value: {} } }] }],
    options: { gap: 'gap-4', mobileStacking: true, equalHeight: false, ...options },
  };
}

function mountToolbar(content: ContentPart) {
  return mount(ContentGridBlockToolbar, {
    props: {
      content,
      blockKey: 'grid-1',
      canMoveUp: true,
      canMoveDown: true,
      canDelete: true,
    },
    global: { stubs },
  });
}

describe('ContentGridBlockToolbar', () => {
  it('carries no row/column management — that lives on the canvas now', () => {
    const wrapper = mountToolbar(makeContent());
    expect(wrapper.find('[data-rc-toolbar-add-row]').exists()).toBe(false);
    expect(wrapper.find('[data-rc-toolbar-add-column]').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('rich-content.grid_row');
  });

  it('changes the gap option', async () => {
    const wrapper = mountToolbar(makeContent());
    const gapSelect = wrapper.findAllComponents(Select)[0]!;
    await gapSelect.vm.$emit('update:modelValue', 'gap-8');

    const updated = wrapper.emitted('update:content')!.at(-1)![0] as ContentPart;
    expect((updated.options as Record<string, unknown>).gap).toBe('gap-8');
  });

  it('toggles mobile stacking, equal height, and dividers independently', async () => {
    const wrapper = mountToolbar(makeContent());
    const switches = wrapper.findAllComponents(Switch);
    expect(switches).toHaveLength(3);

    await switches[0]!.vm.$emit('update:modelValue', false);
    let updated = wrapper.emitted('update:content')!.at(-1)![0] as ContentPart;
    expect((updated.options as Record<string, unknown>).mobileStacking).toBe(false);
    // Untouched siblings keep their defaults.
    expect((updated.options as Record<string, unknown>).equalHeight).toBe(false);

    await switches[2]!.vm.$emit('update:modelValue', true);
    updated = wrapper.emitted('update:content')!.at(-1)![0] as ContentPart;
    expect((updated.options as Record<string, unknown>).dividers).toBe(true);
  });

  it('changes vertical alignment', async () => {
    const wrapper = mountToolbar(makeContent());
    const verticalAlignSelect = wrapper.findAllComponents(Select)[1]!;
    await verticalAlignSelect.vm.$emit('update:modelValue', 'center');

    const updated = wrapper.emitted('update:content')!.at(-1)![0] as ContentPart;
    expect((updated.options as Record<string, unknown>).verticalAlign).toBe('center');
  });

  it('exposes the width picker and section toolbar options', () => {
    const wrapper = mountToolbar(makeContent());
    expect(wrapper.find('.width-picker-stub').exists()).toBe(true);
    expect(wrapper.find('.section-toolbar-stub').exists()).toBe(true);
  });
});
