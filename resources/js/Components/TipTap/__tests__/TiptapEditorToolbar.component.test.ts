import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import TiptapEditor from '../TiptapEditor.vue';
import TiptapInsertMenu from '../TiptapInsertMenu.vue';
import TiptapLinkButton from '../TiptapLinkButton.vue';
import TiptapMoreMenu from '../TiptapMoreMenu.vue';

async function mountEditor(props: Record<string, unknown>) {
  const wrapper = mount(TiptapEditor, { props: { modelValue: null, ...props } });
  await nextTick();
  return wrapper;
}

describe('TiptapEditor field toolbar', () => {
  it('frames an inline-toolbar field by default: one box, tinted toolbar row, flat buttons', async () => {
    const wrapper = await mountEditor({ preset: 'marks', disableLinks: true });

    expect(wrapper.classes()).toContain('tiptap-editor--framed');
    expect(wrapper.find('.tiptap-toolbar').classes()).toContain('border-b');
    expect(wrapper.find('.tiptap-content').classes()).not.toContain('border');
    expect(wrapper.find('[data-testid="tiptap-format-underline"]').classes()).toContain('size-8');
  });

  it('keeps short marks-only fields to bold, italic and underline', async () => {
    const wrapper = await mountEditor({ preset: 'marks', disableLinks: true });

    expect(wrapper.findAll('[data-slot="tiptap-tool"]')).toHaveLength(3);
    expect(wrapper.findComponent(TiptapInsertMenu).exists()).toBe(false);
    expect(wrapper.findComponent(TiptapMoreMenu).exists()).toBe(false);
    expect(wrapper.find('[data-testid="tiptap-heading-select"]').exists()).toBe(false);
  });

  it('shows one compact row for a full field, with the rest behind Insert and More', async () => {
    const wrapper = await mountEditor({ preset: 'full' });

    expect(wrapper.find('[data-testid="tiptap-heading-select"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="tiptap-link"]').exists()).toBe(true);
    expect(wrapper.findComponent(TiptapInsertMenu).props('tools')).toMatchObject({ table: true, video: true });
    expect(wrapper.findComponent(TiptapMoreMenu).props('tools')).toMatchObject({ tag: true, headingStyle: true });
  });

  it('trims a record description to text tools while keeping the full schema', async () => {
    const wrapper = await mountEditor({ tools: 'description', html: true, modelValue: '<table><tr><td>Lentelė</td></tr></table>' });

    expect(wrapper.findComponent(TiptapInsertMenu).props('tools')).toMatchObject({ image: true, youtube: true, table: false, video: false });
    expect(wrapper.findComponent(TiptapMoreMenu).props('tools')).toMatchObject({ tag: false, headingStyle: false, alignment: false });
    // The preset still defaults to `full`, so stored tables survive a load-and-save.
    expect(wrapper.find('.tiptap-content table').exists()).toBe(true);
  });

  // "Hidden, never disabled": remove-link lives in the link bubble, table tools in the table bubble.
  it('keeps contextual link and table actions out of the toolbar row', async () => {
    const wrapper = await mountEditor({ preset: 'full' });
    const toolbar = wrapper.find('.tiptap-toolbar');

    expect(toolbar.find('[data-testid="tiptap-link-remove"]').exists()).toBe(false);
    expect(toolbar.text()).not.toContain('rich-content.table_delete_row');
    const bubbleKeys = wrapper.findAllComponents({ name: 'BubbleMenu' }).map(menu => menu.props('pluginKey'));
    expect(bubbleKeys).toEqual(expect.arrayContaining(['textBubbleMenu', 'linkBubbleMenu', 'tableBubbleMenu', 'imageBubbleMenu']));
  });

  it('labels every toolbar button for screen readers', async () => {
    const wrapper = await mountEditor({ preset: 'full' });

    for (const button of wrapper.find('.tiptap-toolbar').findAll('button')) {
      expect(button.attributes('aria-label') ?? button.attributes('title') ?? button.text(), button.html()).toBeTruthy();
    }
  });

  it('never submits the surrounding form from a toolbar button', async () => {
    const wrapper = await mountEditor({ preset: 'full' });

    for (const button of wrapper.find('.tiptap-toolbar').findAll('[data-slot="tiptap-tool"]')) {
      expect(button.attributes('type')).toBe('button');
    }
  });

  it('can hide bold while retaining italic and underline controls', async () => {
    const wrapper = await mountEditor({ preset: 'full', showBold: false });

    expect(wrapper.find('[data-testid="tiptap-format-bold"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="tiptap-format-italic"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="tiptap-format-underline"]').exists()).toBe(true);
  });
});

describe('TiptapEditor bubble toolbar', () => {
  it('keeps the toolbar in the selection bubble and stays unframed', async () => {
    const wrapper = await mountEditor({ preset: 'compact', toolbar: 'bubble' });

    expect(wrapper.find('.tiptap-toolbar').exists()).toBe(false);
    expect(wrapper.classes()).not.toContain('tiptap-editor--framed');
  });

  it('renders the link dialog for marks, compact, and full presets', async () => {
    for (const preset of ['marks', 'compact', 'full'] as const) {
      const wrapper = await mountEditor({ preset, toolbar: 'bubble' });

      expect(wrapper.findComponent(TiptapLinkButton).exists(), `link dialog for preset ${preset}`).toBe(true);
    }
  });

  it('has no menus or link dialog for the minimal preset', async () => {
    const wrapper = await mountEditor({ preset: 'minimal', toolbar: 'bubble' });

    expect(wrapper.findComponent(TiptapLinkButton).exists()).toBe(false);
    expect(wrapper.findComponent({ name: 'BubbleMenu' }).exists()).toBe(false);
  });

  it('does not render the link dialog when disableLinks is true', async () => {
    const wrapper = await mountEditor({ preset: 'marks', disableLinks: true, toolbar: 'bubble' });

    expect(wrapper.findComponent(TiptapLinkButton).exists()).toBe(false);
  });
});
