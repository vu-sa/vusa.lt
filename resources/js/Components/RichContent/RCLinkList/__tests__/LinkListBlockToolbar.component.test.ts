import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import LinkListBlockToolbar from '../LinkListBlockToolbar.vue';
import type { ContentPart } from '../../Types';

const stubs = {
  RCBlockToolbarShell: {
    props: ['content', 'blockKey', 'reference', 'canMoveUp', 'canMoveDown', 'canDelete'],
    emits: ['move-up', 'move-down', 'delete', 'open-form'],
    template: '<div class="shell-stub"><slot /></div>',
  },
  RCWidthPicker: { props: ['modelValue', 'allowedWidths'], emits: ['update:modelValue'], template: '<div class="width-picker-stub" />' },
  RCSectionToolbarOptions: { props: ['modelValue', 'presentationDisabled'], template: '<div class="section-toolbar-options-stub" />' },
  CollectionSelectDialog: { template: '<div />' },
};

function makeContent(options: Record<string, unknown> = {}, jsonContent: Record<string, unknown> = { links: [] }): ContentPart {
  return {
    type: 'link-list',
    json_content: jsonContent,
    options: { source: 'news', mode: 'latest', tenantScope: 'current', limit: 3, style: 'photo', ...options },
  };
}

function mountToolbar(content: ContentPart) {
  return mount(LinkListBlockToolbar, {
    props: { content, blockKey: 'link-list-1', canMoveUp: true, canMoveDown: true, canDelete: true },
    global: { stubs },
  });
}

describe('LinkListBlockToolbar', () => {
  it('renders the fetch-options fields (source/style toggle) inside the popover', () => {
    const wrapper = mountToolbar(makeContent());
    expect(wrapper.text()).toContain('link_list_source_news');
    expect(wrapper.text()).toContain('link_list_style_photo');
  });

  it('toggling tenant scope to "all" emits update:content with the merged options', async () => {
    const wrapper = mountToolbar(makeContent());
    const allButton = wrapper.findAll('button').find(b => b.text().includes('tenant_scope_all'));
    await allButton!.trigger('click');

    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    const last = emitted!.at(-1)![0] as ContentPart;
    expect(last.options?.tenantScope).toBe('all');
    // The rest of the options must survive the merge, not just the changed field.
    expect(last.options?.source).toBe('news');
  });

  it('self-heals null options on mount so the fields have something to mutate', async () => {
    const wrapper = mountToolbar({ type: 'link-list', json_content: { links: [] }, options: null });
    const emitted = wrapper.emitted('update:content');
    expect(emitted).toBeTruthy();
    expect((emitted!.at(-1)![0] as ContentPart).options).toBeTruthy();
  });

  it('shows the width picker and section toolbar options', () => {
    const wrapper = mountToolbar(makeContent());
    expect(wrapper.find('.width-picker-stub').exists()).toBe(true);
    expect(wrapper.find('.section-toolbar-options-stub').exists()).toBe(true);
  });

  it('renders compact manual links with add link button when source is manual', async () => {
    const wrapper = mountToolbar(makeContent({ source: 'manual' }, { links: [{ title: 'Test link', url: 'https://vusa.lt' }] }));
    expect(wrapper.find('[data-rc-toolbar-add-link]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Test link');
  });
});
