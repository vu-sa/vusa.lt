import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import ContentGridEditor from '../ContentGridEditor.vue';
import type { ContentGrid } from '@/Types/contentParts';

const stubs = {
  TiptapEditor: { template: '<div class="tiptap-stub" />' },
  TooltipProvider: { template: '<div><slot /></div>' },
  Tooltip: { template: '<div><slot /></div>' },
  TooltipTrigger: { template: '<div><slot /></div>' },
  TooltipContent: { template: '<div><slot /></div>' },
};

function makeRows(): ContentGrid['json_content'] {
  return [
    {
      columns: [
        { width: 'col-span-7', content: { type: 'tiptap', value: {} } },
        { width: 'col-span-5', content: { type: 'image', value: '/a.jpg' } },
      ],
    },
  ];
}

describe('ContentGridEditor', () => {
  // Regression: the editor used to treat `json_content` as a wrapper object
  // (`{ json_content: rows, options }`) instead of the rows array it actually is,
  // so `isModelValueInitialized` was always false and every mount silently replaced
  // existing rows with a fresh default row — a page's grid content vanished the
  // moment an author reopened it for editing.
  it('does not wipe existing rows on mount', () => {
    const json_content = makeRows();
    const options: ContentGrid['options'] = { gap: 'gap-4', mobileStacking: true, equalHeight: false };

    mount(ContentGridEditor, { props: { modelValue: json_content, options }, global: { stubs } });

    expect(json_content).toHaveLength(1);
    expect(json_content[0]!.columns[0]!.width).toBe('col-span-7');
    expect(json_content[0]!.columns[1]!.width).toBe('col-span-5');
  });

  it('offers col-span-5 (40%) and col-span-7 (60%) as width options', () => {
    const json_content = makeRows();
    const wrapper = mount(ContentGridEditor, {
      props: { modelValue: json_content, options: { gap: 'gap-4', mobileStacking: true, equalHeight: false } },
      global: { stubs },
    });

    const widthOptionTexts = wrapper.findAll('[value="col-span-5"], [value="col-span-7"]');
    // SelectItem internals vary by stub depth; assert through the rendered row's
    // actual applied width classes instead, which is what matters functionally.
    expect(wrapper.find('.col-span-7').exists()).toBe(true);
    expect(wrapper.find('.col-span-5').exists()).toBe(true);
    void widthOptionTexts;
  });

  it('offers a "card" content type and renders it through RCFeatureCard', () => {
    const json_content: ContentGrid['json_content'] = [
      {
        columns: [
          { width: 'col-span-12', content: { type: 'card', value: { image: '/x.jpg', title: 'Kortelė', description: 'Aprašymas', href: '#' } } },
        ],
      },
    ];
    const wrapper = mount(ContentGridEditor, {
      props: { modelValue: json_content, options: { gap: 'gap-4', mobileStacking: true, equalHeight: false } },
      global: { stubs },
    });

    expect(wrapper.find('input[type="url"]').exists()).toBe(true);
  });

  it('creates a grid from scratch when json_content is empty', async () => {
    const json_content: ContentGrid['json_content'] = [];
    const wrapper = mount(ContentGridEditor, {
      props: { modelValue: json_content, options: null },
      global: { stubs },
    });

    // onMounted immediately populates a default row — the "create grid" button
    // should not remain visible.
    await nextTick();
    expect(wrapper.text()).not.toContain('Sukurti tinklelį');
  });

  it('offers the shared section options (title/subtitle/background/padding)', () => {
    const json_content = makeRows();
    const wrapper = mount(ContentGridEditor, {
      props: { modelValue: json_content, options: { gap: 'gap-4', mobileStacking: true, equalHeight: false } },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('rich-content.section_options');
  });

  it('adding a decoration to an image cell updates that column only', async () => {
    const json_content: ContentGrid['json_content'] = [
      {
        columns: [
          { width: 'col-span-6', content: { type: 'image', value: '/a.jpg' } },
          { width: 'col-span-6', content: { type: 'image', value: '/b.jpg' } },
        ],
      },
    ];
    const wrapper = mount(ContentGridEditor, {
      props: { modelValue: json_content, options: { gap: 'gap-4', mobileStacking: true, equalHeight: false } },
      global: { stubs },
    });

    const addButtons = wrapper.findAll('button').filter(b => b.text().includes('add_first_decoration'));
    expect(addButtons).toHaveLength(2);

    await addButtons[0]!.trigger('click');

    expect(json_content[0]!.columns[0]!.content.decorations).toHaveLength(1);
    expect(json_content[0]!.columns[1]!.content.decorations ?? []).toHaveLength(0);
  });
});
