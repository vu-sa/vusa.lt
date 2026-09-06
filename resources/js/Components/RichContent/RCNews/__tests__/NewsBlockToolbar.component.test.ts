import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import NewsBlockToolbar from '../NewsBlockToolbar.vue';
import type { ContentPart } from '../../Types';

const stubs = {
  RCBlockToolbarShell: { template: '<div><slot /></div>' },
  RCWidthPicker: { template: '<div class="width-picker" />' },
  RCPresentationPicker: { template: '<div class="presentation-picker" />' },
  RCCategoryAliasSelect: {
    emits: ['update:modelValue'],
    template: '<button class="category-picker" @click="$emit(\'update:modelValue\', \'announcements\')" />',
  },
  RCTagAliasSelect: {
    emits: ['update:modelValue'],
    template: '<button class="tag-picker" @click="$emit(\'update:modelValue\', \'important\')" />',
  },
  RCTenantMultiSelect: { template: '<div class="tenant-picker" />' },
  NumberField: { template: '<div />' },
  ToggleGroup: { template: '<div><slot /></div>' },
  ToggleGroupItem: { template: '<button><slot /></button>' },
};

function mountToolbar(options: Record<string, unknown> = { tenantScope: 'all', limit: 4 }) {
  return mount(NewsBlockToolbar, {
    props: {
      content: { type: 'news', json_content: { title: '', eyebrow: '' }, options } as ContentPart,
      blockKey: 'news-1',
      canMoveUp: true,
      canMoveDown: true,
      canDelete: true,
    },
    global: { stubs },
  });
}

describe('NewsBlockToolbar', () => {
  it('offers category, tag, tenant and count controls', () => {
    const wrapper = mountToolbar();

    expect(wrapper.text()).toContain('category_alias');
    expect(wrapper.text()).toContain('tag_alias');
    expect(wrapper.text()).toContain('tenant_scope');
    expect(wrapper.text()).toContain('limit');
  });

  it('merges a selected tag into the news feed options', async () => {
    const wrapper = mountToolbar({ tenantScope: 'all', limit: 4 });
    await wrapper.find('.tag-picker').trigger('click');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const content = emitted!.at(-1)![0] as ContentPart;
    expect(content.options).toMatchObject({ tagAlias: 'important', tenantScope: 'all', limit: 4 });
  });
});
