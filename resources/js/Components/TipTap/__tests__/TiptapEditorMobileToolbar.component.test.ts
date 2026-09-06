import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import TiptapEditor from '../TiptapEditor.vue';

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
