import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RCBlockCard from '../RCBlockCard.vue';

import { commonStubs } from '@/tests/stubs';

import type { ContentPart } from '../../Types';

const baseProps = {
  collapsed: true,
  previewMode: false,
  canMoveUp: false,
  canMoveDown: false,
  canDelete: true,
};

function mountCard(content: ContentPart) {
  return mount(RCBlockCard, {
    props: { ...baseProps, content },
    global: {
      stubs: {
        ...commonStubs,
        // Heavy/async children — irrelevant to the header indicator under test.
        ContentEditorFactory: true,
        RCSideBySideDialog: true,
        TextBoxSubmissionsDialog: true,
      },
    },
  });
}

/** The indicator chip carries the section_indicator tooltip title; that attribute is
 *  the most precise hook (the chip's visible text is a tiny "H{level}" label). */
function indicator(wrapper: ReturnType<typeof mountCard>) {
  return wrapper.find('[title="rich-content.section_indicator"]');
}

describe('RCBlockCard section indicator', () => {
  it('shows the chip with the resolved heading level for a section block with a title', () => {
    const wrapper = mountCard({
      type: 'shadcn-accordion',
      json_content: [],
      options: { title: 'DUK', background: 'none' },
      key: 'k1',
    });

    expect(indicator(wrapper).exists()).toBe(true);
    expect(indicator(wrapper).text()).toBe('H2');
  });

  it('reflects a non-default heading level', () => {
    const wrapper = mountCard({
      type: 'shadcn-accordion',
      json_content: [],
      options: { title: 'DUK', headingLevel: 3 },
      key: 'k2',
    });

    expect(indicator(wrapper).text()).toBe('H3');
  });

  it('hides the chip when the title is empty, even with a non-none background', () => {
    const wrapper = mountCard({
      type: 'shadcn-accordion',
      json_content: [],
      options: { background: 'muted' },
      key: 'k3',
    });

    // The chip represents the section header; no title means no header to indicate.
    expect(indicator(wrapper).exists()).toBe(false);
  });

  it('hides the chip when the section has neither a title nor a non-none background', () => {
    const wrapper = mountCard({
      type: 'shadcn-accordion',
      json_content: [],
      options: { background: 'none' },
      key: 'k4',
    });

    expect(indicator(wrapper).exists()).toBe(false);
  });

  it('never shows the chip for a block that does not use section chrome', () => {
    const wrapper = mountCard({
      type: 'tiptap',
      json_content: {},
      options: null,
      key: 'k5',
    } as unknown as ContentPart);

    expect(indicator(wrapper).exists()).toBe(false);
  });
});
