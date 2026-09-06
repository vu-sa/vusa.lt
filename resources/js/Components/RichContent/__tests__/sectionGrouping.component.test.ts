import { describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';

import RichContentParser from '../RichContentParser.vue';

/**
 * `section` is a marker block — RichContentParser's `groupedContent` wraps every part
 * that follows it (up to the next section marker) inside a real `<section>` element.
 * See RCSection/SectionDisplay.vue and the `groupedContent` computed for the rules.
 */
describe('RichContentParser section grouping', () => {
  it('wraps following blocks inside the section element until the next marker', () => {
    const wrapper = mount(RichContentParser, {
      props: {
        content: [
          { id: 1, type: 'section', json_content: {}, options: { title: 'First' } },
          { id: 2, type: 'tiptap', json_content: {}, options: {} },
          { id: 3, type: 'tiptap', json_content: {}, options: {} },
          { id: 4, type: 'section', json_content: {}, options: { title: 'Second' } },
          { id: 5, type: 'tiptap', json_content: {}, options: {} },
        ] as unknown as models.ContentPart[],
      },
    });

    const sections = wrapper.findAll('section');
    expect(sections).toHaveLength(2);
    // The first section's rc-canvas holds exactly the two tiptap blocks before "Second".
    expect(sections[0]!.findAll('.rc-canvas-nested > *')).toHaveLength(2);
    expect(sections[1]!.findAll('.rc-canvas-nested > *')).toHaveLength(1);
  });

  it('does not render a nested canvas for a section with no children', () => {
    const wrapper = mount(RichContentParser, {
      props: {
        content: [
          { id: 1, type: 'section', json_content: {}, options: { title: 'Header only' } },
        ] as unknown as models.ContentPart[],
      },
    });

    expect(wrapper.find('section').exists()).toBe(true);
    expect(wrapper.find('.rc-canvas-nested').exists()).toBe(false);
  });

  it('wraps: "none" renders header-only and does not absorb the next blocks', () => {
    const wrapper = mount(RichContentParser, {
      props: {
        content: [
          { id: 1, type: 'section', json_content: {}, options: { title: 'Divider', wraps: 'none' } },
          { id: 2, type: 'tiptap', json_content: {}, options: {} },
        ] as unknown as models.ContentPart[],
      },
    });

    const sections = wrapper.findAll('section');
    expect(sections).toHaveLength(1);
    expect(sections[0]!.find('.rc-canvas-nested').exists()).toBe(false);
    // The tiptap block after it renders as an ordinary top-level block, outside the section.
    expect(wrapper.html()).toContain('Divider');
  });

  it('ends a manual section before a self-spaced band', async () => {
    const wrapper = mount(RichContentParser, {
      props: {
        content: [
          { id: 1, type: 'section', json_content: {}, options: { title: 'Grid section' } },
          { id: 2, type: 'content-grid', json_content: [], options: {} },
          { id: 3, type: 'card-stack', json_content: [], options: {} },
        ] as unknown as models.ContentPart[],
      },
    });

    await flushPromises();
    await vi.dynamicImportSettled();

    expect(wrapper.find('.rc-canvas-nested')!.findAll(':scope > *')).toHaveLength(1);
  });

  it('renders a plain block with no section wrapper when there is no marker', () => {
    const wrapper = mount(RichContentParser, {
      props: {
        content: [
          { id: 1, type: 'tiptap', json_content: {}, options: {} },
        ] as unknown as models.ContentPart[],
      },
    });

    expect(wrapper.find('section').exists()).toBe(false);
  });
});
