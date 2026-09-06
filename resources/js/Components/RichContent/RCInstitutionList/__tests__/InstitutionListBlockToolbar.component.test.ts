import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import InstitutionListBlockToolbar from '../InstitutionListBlockToolbar.vue';
import type { ContentPart } from '../../Types';

const stubs = {
  RCBlockToolbarShell: { template: '<div><slot /></div>' },
  RCWidthPicker: { template: '<div class="width-picker" />' },
  RCPresentationPicker: { template: '<div class="presentation-picker" />' },
  RCTypeSlugSelect: {
    emits: ['update:modelValue'],
    template: '<button class="type-picker" @click="$emit(\'update:modelValue\', \'pkp\')" />',
  },
  RCTenantMultiSelect: { template: '<div class="tenant-picker" />' },
  NumberField: { template: '<div />' },
  ToggleGroup: { template: '<div><slot /></div>' },
  ToggleGroupItem: { template: '<button><slot /></button>' },
};

function mountToolbar(options: Record<string, unknown> = { tenantScope: 'all', typeSlug: 'pkp' }) {
  return mount(InstitutionListBlockToolbar, {
    props: {
      content: { type: 'institution-list', json_content: { title: '', eyebrow: '' }, options } as ContentPart,
      blockKey: 'inst-1',
      canMoveUp: true,
      canMoveDown: true,
      canDelete: true,
    },
    global: { stubs },
  });
}

describe('InstitutionListBlockToolbar', () => {
  it('renders width and options controls', () => {
    const wrapper = mountToolbar();

    expect(wrapper.find('.width-picker').exists()).toBe(true);
    expect(wrapper.find('.presentation-picker').exists()).toBe(true);
    expect(wrapper.find('.type-picker').exists()).toBe(true);
  });

  it('updates options when type is selected', async () => {
    const wrapper = mountToolbar({ tenantScope: 'all', typeSlug: 'other' });
    await wrapper.find('.type-picker').trigger('click');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const content = emitted!.at(-1)![0] as ContentPart;
    expect(content.options).toMatchObject({ typeSlug: 'pkp', tenantScope: 'all' });
  });
});
