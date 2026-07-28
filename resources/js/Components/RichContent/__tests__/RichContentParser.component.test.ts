import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RichContentParser from '../RichContentParser.vue';

/**
 * Covers the width-class resolution introduced by the rc-canvas rework:
 * each block resolves to a canvas column from `options.width`, falling back
 * to a per-type default, and self-spaced section types (hero, accordion,
 * galleries, …) get `rc-flush` so the canvas doesn't double their internal
 * vertical padding.
 */
describe('RichContentParser width classes', () => {
  it('defaults a text block to the prose column (no width class) with no rc-flush', () => {
    const wrapper = mount(RichContentParser, {
      props: {
        content: [{ id: 1, type: 'tiptap', json_content: {}, options: {} }] as unknown as models.ContentPart[],
      },
    });

    // The tiptap branch is synchronous, so the width class lands directly on the component root.
    expect(wrapper.html()).not.toContain('rc-full');
    expect(wrapper.html()).not.toContain('rc-flush');
  });

  it('defaults a self-spaced type (hero) to rc-full + rc-flush', async () => {
    const wrapper = mount(RichContentParser, {
      props: {
        content: [{
          id: 1,
          type: 'hero',
          json_content: { title: 'T', description: '', imageSrc: '', imageAlt: '', objectPosition: '', overlayContent: { title: '', subtitle: '' }, buttons: [] },
          options: {},
        }] as unknown as models.ContentPart[],
      },
    });

    // Async component — the wrapper <div> around <Suspense> carries the width classes.
    const wrapperDiv = wrapper.find('div');
    expect(wrapperDiv.classes()).toContain('rc-full');
    expect(wrapperDiv.classes()).toContain('rc-flush');
  });

  it('lets options.width override the type default', () => {
    const wrapper = mount(RichContentParser, {
      props: {
        content: [{
          id: 1,
          type: 'hero',
          json_content: { title: 'T', description: '', imageSrc: '', imageAlt: '', objectPosition: '', overlayContent: { title: '', subtitle: '' }, buttons: [] },
          options: { width: 'content' },
        }] as unknown as models.ContentPart[],
      },
    });

    const wrapperDiv = wrapper.find('div');
    expect(wrapperDiv.classes()).toContain('rc-content');
    expect(wrapperDiv.classes()).not.toContain('rc-full');
    // Still self-spaced (hero renders its own section chrome) regardless of chosen width.
    expect(wrapperDiv.classes()).toContain('rc-flush');
  });
});
