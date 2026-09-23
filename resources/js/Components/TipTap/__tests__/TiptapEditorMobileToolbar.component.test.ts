import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import TiptapEditor from '../TiptapEditor.vue';
import TiptapLinkButton from '../TiptapLinkButton.vue';

/**
 * The full toolbar is too wide for a phone screen above the keyboard, so most
 * controls collapse behind a mobile-only toggle (visible controls stay full-size
 * on desktop via `sm:contents` — that CSS behavior itself is not assertable in
 * jsdom, so this only checks the wiring: the toggle's click handler and the
 * class it drives).
 */
describe('TiptapEditor mobile toolbar collapse', () => {
  it('starts with the extra controls collapsed and the formatting buttons always visible', async () => {
    const wrapper = mount(TiptapEditor, {
      props: { modelValue: null, preset: 'full' },
    });
    await nextTick();

    const extra = wrapper.find('[data-testid="tiptap-toolbar-extra"]');
    expect(extra.exists()).toBe(true);
    expect(extra.classes()).toContain('hidden');
    expect(extra.classes()).toContain('sm:contents');

    // Bold/italic/underline and the toggle itself live outside the collapsible
    // wrapper, so they appear earlier in the toolbar's markup.
    const html = wrapper.html();
    const toggleIndex = html.indexOf('data-testid="tiptap-toolbar-mobile-toggle"');
    const extraIndex = html.indexOf('data-testid="tiptap-toolbar-extra"');
    expect(toggleIndex).toBeGreaterThan(-1);
    expect(toggleIndex).toBeLessThan(extraIndex);
  });

  it('expands and re-collapses the extra controls when the mobile toggle is clicked', async () => {
    const wrapper = mount(TiptapEditor, {
      props: { modelValue: null, preset: 'full' },
    });
    await nextTick();

    const toggle = wrapper.find('[data-testid="tiptap-toolbar-mobile-toggle"]');
    expect(toggle.exists()).toBe(true);

    await toggle.trigger('click');
    let extra = wrapper.find('[data-testid="tiptap-toolbar-extra"]');
    expect(extra.classes()).toEqual(['contents']);

    await toggle.trigger('click');
    extra = wrapper.find('[data-testid="tiptap-toolbar-extra"]');
    expect(extra.classes()).toContain('hidden');
    expect(extra.classes()).toContain('sm:contents');
  });

  it('keeps the toolbar in the selection bubble when requested', async () => {
    const wrapper = mount(TiptapEditor, {
      props: { modelValue: null, preset: 'compact', toolbar: 'bubble' },
    });
    await nextTick();

    expect(wrapper.find('.tiptap-toolbar').exists()).toBe(false);
  });

  it('can hide bold while retaining italic and underline controls', async () => {
    const wrapper = mount(TiptapEditor, {
      props: { modelValue: null, preset: 'full', showBold: false },
    });
    await nextTick();

    expect(wrapper.find('[data-testid="tiptap-format-bold"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="tiptap-format-italic"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="tiptap-format-underline"]').exists()).toBe(true);
  });
});

describe('TiptapEditor bubble menu link support', () => {
  it('renders link button in bubble menu for marks, compact, and full presets', async () => {
    for (const preset of ['marks', 'compact', 'full'] as const) {
      const wrapper = mount(TiptapEditor, {
        props: { modelValue: null, preset, toolbar: 'bubble' },
      });
      await nextTick();

      const linkButton = wrapper.findComponent(TiptapLinkButton);
      expect(linkButton.exists(), `link button should exist for preset ${preset}`).toBe(true);
    }
  });

  it('does not render bubble menu or link button for minimal preset', async () => {
    const wrapper = mount(TiptapEditor, {
      props: { modelValue: null, preset: 'minimal', toolbar: 'bubble' },
    });
    await nextTick();

    const linkButton = wrapper.findComponent(TiptapLinkButton);
    expect(linkButton.exists()).toBe(false);
  });

  it('does not render link button when disableLinks is true', async () => {
    const wrapper = mount(TiptapEditor, {
      props: { modelValue: null, preset: 'marks', disableLinks: true, toolbar: 'bubble' },
    });
    await nextTick();

    const linkButton = wrapper.findComponent(TiptapLinkButton);
    expect(linkButton.exists()).toBe(false);
  });
});

describe('TiptapEditor framed field', () => {
  it('draws one field box with a tinted toolbar row and flat formatting buttons', async () => {
    const wrapper = mount(TiptapEditor, {
      props: { modelValue: null, preset: 'marks', disableLinks: true, framed: true },
    });
    await nextTick();

    expect(wrapper.classes()).toContain('tiptap-editor--framed');
    expect(wrapper.find('.tiptap-toolbar').classes()).toContain('border-b');
    expect(wrapper.find('.tiptap-content').classes()).not.toContain('rounded-md');
    expect(wrapper.find('[data-testid="tiptap-format-underline"]').classes()).toContain('size-8');
  });

  it('drops the selection bubble, whose controls the toolbar row already shows', async () => {
    const wrapper = mount(TiptapEditor, {
      props: { modelValue: null, preset: 'marks', disableLinks: true, framed: true },
    });
    await nextTick();

    expect(wrapper.findComponent({ name: 'BubbleMenu' }).exists()).toBe(false);
  });

    it('keeps the standalone toolbar card by default', async () => {
    const wrapper = mount(TiptapEditor, {
      props: { modelValue: null, preset: 'marks', disableLinks: true },
    });
    await nextTick();

    expect(wrapper.classes()).not.toContain('tiptap-editor--framed');
    expect(wrapper.find('.tiptap-toolbar').classes()).toContain('rounded-lg');
    expect(wrapper.findComponent({ name: 'BubbleMenu' }).exists()).toBe(true);
  });
});
