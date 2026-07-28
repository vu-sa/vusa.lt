import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import HeroForm from '../HeroForm.vue';
import { createContentItem } from '../../Types';
import type { Hero } from '@/Types/contentParts';

const stubs = {
  TiptapEditor: { template: '<div class="tiptap-stub" />' },
  TiptapImageButton: { template: '<button class="image-btn-stub"><slot /></button>' },
};

function makeItem(): { json_content: Hero['json_content']; options: Hero['options'] } {
  const item = createContentItem('hero');
  return { json_content: item.json_content, options: item.options };
}

describe('HeroForm', () => {
  it('hides eyebrow and description for the banner variant, which renders neither', async () => {
    const item = makeItem();
    const wrapper = mount(HeroForm, { props: { modelValue: item.json_content, options: item.options }, global: { stubs } });

    expect(wrapper.text()).toContain('rich-content.eyebrow');
    expect(wrapper.text()).toContain('rich-content.description');

    const bannerButton = wrapper.findAll('button').find(b => b.text().includes('rich-content.hero_variant_banner'));
    await bannerButton!.trigger('click');

    expect(wrapper.text()).not.toContain('rich-content.eyebrow');
    expect(wrapper.text()).not.toContain('rich-content.description');
    expect(item.options.variant).toBe('banner');
  });

  it('shows the "only first button" hint only for the banner variant', async () => {
    const item = makeItem();
    const wrapper = mount(HeroForm, { props: { modelValue: item.json_content, options: item.options }, global: { stubs } });
    expect(wrapper.text()).not.toContain('rich-content.hero_banner_buttons_hint');

    const bannerButton = wrapper.findAll('button').find(b => b.text().includes('rich-content.hero_variant_banner'));
    await bannerButton!.trigger('click');
    expect(wrapper.text()).toContain('rich-content.hero_banner_buttons_hint');
  });

  it('switches variant when a different skeleton option is clicked', async () => {
    const item = makeItem();
    const wrapper = mount(HeroForm, { props: { modelValue: item.json_content, options: item.options }, global: { stubs } });

    const panelButton = wrapper.findAll('button').find(b => b.text().includes('rich-content.hero_variant_panel'));
    await panelButton!.trigger('click');

    expect(item.options.variant).toBe('panel');
  });

  it('adds an image decoration through the shared RCDecorationListEditor', async () => {
    const item = makeItem();
    const wrapper = mount(HeroForm, { props: { modelValue: item.json_content, options: item.options }, global: { stubs } });

    const before = item.options.imageDecorations?.length ?? 0;
    const addButton = wrapper.findAll('button').find(b => b.text().includes('add_first_decoration') || b.text().includes('add_decoration'));
    expect(addButton).toBeTruthy();
    await addButton!.trigger('click');

    expect(item.options.imageDecorations?.length).toBe(before + 1);
  });
});
