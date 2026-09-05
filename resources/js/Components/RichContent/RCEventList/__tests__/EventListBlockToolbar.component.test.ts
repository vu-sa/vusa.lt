import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import EventListBlockToolbar from '../EventListBlockToolbar.vue';
import type { ContentPart } from '../../Types';

const stubs = {
  RCBlockToolbarShell: {
    props: ['content', 'blockKey', 'reference', 'canMoveUp', 'canMoveDown', 'canDelete'],
    emits: ['move-up', 'move-down', 'delete', 'open-form'],
    template: '<div class="shell-stub"><slot /></div>',
  },
  RCWidthPicker: { props: ['modelValue', 'allowedWidths'], emits: ['update:modelValue'], template: '<div class="width-picker-stub" />' },
  RCPresentationPicker: { props: ['modelValue'], emits: ['update:modelValue'], template: '<div class="presentation-picker-stub" />' },
};

function makeContent(options: Record<string, unknown> = {}): ContentPart {
  return {
    type: 'event-list',
    json_content: {},
    options: { mode: 'upcoming', tenantScope: 'current', groupBy: 'none', limit: 12, style: 'cards', ...options },
  };
}

function mountToolbar(content: ContentPart) {
  return mount(EventListBlockToolbar, {
    props: { content, blockKey: 'event-list-1', canMoveUp: true, canMoveDown: true, canDelete: true },
    global: { stubs },
  });
}

describe('EventListBlockToolbar', () => {
  it('renders the fetch-options fields (mode toggle) inside the popover', () => {
    const wrapper = mountToolbar(makeContent());
    expect(wrapper.text()).toContain('event_list_mode_upcoming');
  });

  it('toggling groupBy to tenant emits update:content with the merged options', async () => {
    const wrapper = mountToolbar(makeContent());
    const tenantGroupButton = wrapper.findAll('button').find(b => b.text().includes('group_by_tenant'));
    await tenantGroupButton!.trigger('click');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const last = emitted!.at(-1)![0] as ContentPart;
    expect(last.options?.groupBy).toBe('tenant');
    // The rest of the options must survive the merge, not just the changed field.
    expect(last.options?.mode).toBe('upcoming');
  });

  it('self-heals null options on mount so the fields have something to mutate', async () => {
    const wrapper = mountToolbar({ type: 'event-list', json_content: {}, options: null });
    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as ContentPart).options).toBeTruthy();
  });

  it('shows the width and presentation pickers', () => {
    const wrapper = mountToolbar(makeContent());
    expect(wrapper.find('.width-picker-stub').exists()).toBe(true);
    expect(wrapper.find('.presentation-picker-stub').exists()).toBe(true);
  });
});
