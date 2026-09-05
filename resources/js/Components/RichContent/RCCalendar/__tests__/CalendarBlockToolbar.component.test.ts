import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import { usePage } from '@inertiajs/vue3';
import CalendarBlockToolbar from '../CalendarBlockToolbar.vue';
import type { ContentPart } from '../../Types';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mocked(usePage).mockReturnValue(createMockPage({
  tenants: [
    { id: 1, shortname: 'VU SA IF' },
    { id: 2, shortname: 'VU SA FF' },
  ],
}));

const stubs = {
  RCBlockToolbarShell: {
    props: ['content', 'blockKey', 'reference', 'canMoveUp', 'canMoveDown', 'canDelete'],
    emits: ['move-up', 'move-down', 'delete', 'open-form'],
    template: '<div class="shell-stub"><slot /></div>',
  },
  RCWidthPicker: { props: ['modelValue', 'allowedWidths'], emits: ['update:modelValue'], template: '<div class="width-picker-stub" />' },
  RCPresentationPicker: {
    props: ['modelValue', 'plainPadding', 'disabled'],
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
  RCTenantMultiSelect: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<button type="button" class="tenant-multi-select-stub" @click="$emit(\'update:modelValue\', [])">tenants</button>',
  },
};

function makeContent(options: Record<string, unknown> = { tenantScope: 'all' }): ContentPart {
  return { type: 'calendar', json_content: { title: '' }, options };
}

function mountToolbar(content: ContentPart) {
  return mount(CalendarBlockToolbar, {
    props: { content, blockKey: 'calendar-1', canMoveUp: true, canMoveDown: true, canDelete: true },
    global: { stubs },
  });
}

describe('CalendarBlockToolbar', () => {
  it('renders the tenant scope picker', () => {
    const wrapper = mountToolbar(makeContent());
    expect(wrapper.text()).toContain('tenant_scope');
    expect(wrapper.find('.tenant-multi-select-stub').exists()).toBe(true);
  });

  it('changing the tenant scope emits update:content with the merged options', async () => {
    const wrapper = mountToolbar(makeContent({ tenantScope: 'all', limit: 3 }));
    await wrapper.find('.tenant-multi-select-stub').trigger('click');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const last = emitted!.at(-1)![0] as ContentPart;
    expect(last.options?.tenantScope).toEqual([]);
    // The rest of the options must survive the merge, not just the changed field.
    expect(last.options?.limit).toBe(3);
  });

  it('renders the limit and category alias fields, and changing them emits update:content', async () => {
    const wrapper = mountToolbar(makeContent({ tenantScope: 'all', limit: 3 }));
    expect(wrapper.text()).toContain('category_alias');
    expect(wrapper.text()).toContain('limit');

    const categorySelect = wrapper.find('select');
    await categorySelect.setValue('freshmen-camps');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as ContentPart).options?.categoryAlias).toBe('freshmen-camps');
  });

  it('self-heals null options on mount so the fields have something to mutate', () => {
    const wrapper = mountToolbar({ type: 'calendar', json_content: { title: '' }, options: null });
    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as ContentPart).options).toBeTruthy();
  });

  it('shows the width and presentation pickers — calendar is bandRole: "band"', () => {
    const wrapper = mountToolbar(makeContent());
    expect(wrapper.find('.width-picker-stub').exists()).toBe(true);
    expect(wrapper.find('.presentation-picker-stub').exists()).toBe(true);
  });
});
