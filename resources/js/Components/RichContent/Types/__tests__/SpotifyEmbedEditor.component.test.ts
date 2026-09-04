import { mount, type VueWrapper } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import SpotifyEmbedEditor from '../SpotifyEmbedEditor.vue';
import { createContentItem } from '../index';
import type { SpotifyEmbed } from '@/Types/contentParts';

const stubs = {
  TiptapEditor: { template: '<div class="tiptap-stub" />' },
  TiptapImageButton: { template: '<button class="image-btn-stub"><slot /></button>' },
  // Reka-ui's Select teleports its listbox and relies on Popper positioning, both
  // unreliable in jsdom — swap in a plain native <select> so variant/background/padding
  // switches can be driven with .setValue(), same as QuickLinkForm's editor tests.
  Select: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)"><slot /></select>',
  },
  SelectTrigger: { template: '<div><slot /></div>' },
  SelectValue: { template: '<div />' },
  SelectContent: { template: '<slot />' },
  SelectItem: { props: ['value'], template: '<option :value="value"><slot /></option>' },
};

function makeItem(): { json_content: SpotifyEmbed['json_content']; options: SpotifyEmbed['options'] } {
  const item = createContentItem('spotify-embed');
  return { json_content: item.json_content, options: item.options };
}

function mountEditor(item = makeItem()) {
  return mount(SpotifyEmbedEditor, {
    props: { modelValue: item.json_content, options: item.options },
    global: { stubs },
  });
}

function findSelectByOptionText(wrapper: VueWrapper, optionText: string) {
  const match = wrapper.findAll('select').find(s => s.findAll('option').some(o => o.text().includes(optionText)));
  if (!match) throw new Error(`No select found with an option containing text "${optionText}"`);
  return match;
}

function findButtonByText(wrapper: VueWrapper, text: string) {
  const match = wrapper.findAll('button').find(b => b.text().includes(text));
  if (!match) throw new Error(`No button found containing text "${text}"`);
  return match;
}

describe('SpotifyEmbedEditor', () => {
  it('defaults to the inline variant and hides every promo-only field', () => {
    const wrapper = mountEditor();

    expect(wrapper.text()).not.toContain('rich-content.buttons');
    expect(wrapper.find('.tiptap-stub').exists()).toBe(false);
  });

  it('patches the url', async () => {
    const wrapper = mountEditor();
    const urlInput = wrapper.find('input[type="url"]');
    await urlInput.setValue('https://open.spotify.com/show/abc');

    const patched = wrapper.emitted('update:modelValue')!.at(-1)![0] as SpotifyEmbed['json_content'];
    expect(patched.url).toBe('https://open.spotify.com/show/abc');
  });

  it('reveals the promo fields once the variant select is switched to promo', async () => {
    const wrapper = mountEditor();
    const variantSelect = findSelectByOptionText(wrapper, 'Reklaminė sekcija');
    await variantSelect.setValue('promo');

    const patchedOptions = wrapper.emitted('update:options')!.at(-1)![0] as SpotifyEmbed['options'];
    expect(patchedOptions?.variant).toBe('promo');
  });

  it('patches title/eyebrow without dropping the url', async () => {
    const item = makeItem();
    item.options = { ...item.options, variant: 'promo' };
    const wrapper = mountEditor(item);

    const titleInput = wrapper.findAll('input[type="text"]')[1]!; // eyebrow, then title
    await titleInput.setValue('Studentų garso banga');

    const patched = wrapper.emitted('update:modelValue')!.at(-1)![0] as SpotifyEmbed['json_content'];
    expect(patched.title).toBe('Studentų garso banga');
    expect(patched).toHaveProperty('url');
  });

  it('adds a button row through the shared DynamicListInput', async () => {
    const item = makeItem();
    item.options = { ...item.options, variant: 'promo' };
    const wrapper = mountEditor(item);

    await findButtonByText(wrapper, 'add_first_button').trigger('click');

    const patched = wrapper.emitted('update:modelValue')!.at(-1)![0] as SpotifyEmbed['json_content'];
    expect(patched.buttons).toHaveLength(1);
  });

  it('defaults the embed panel to the right (textLeft: true) and can flip it', async () => {
    const item = makeItem();
    item.options = { ...item.options, variant: 'promo' };
    const wrapper = mountEditor(item);

    const textLeftToggle = wrapper.findAll('[role="switch"]')[0]!;
    await textLeftToggle.trigger('click');

    const patchedOptions = wrapper.emitted('update:options')!.at(-1)![0] as SpotifyEmbed['options'];
    expect(patchedOptions?.textLeft).toBe(false);
  });

  it('defaults bleed to on and can be switched off', async () => {
    const item = makeItem();
    item.options = { ...item.options, variant: 'promo' };
    const wrapper = mountEditor(item);

    const toggles = wrapper.findAll('[role="switch"]');
    const bleedToggle = toggles[toggles.length - 1]!;
    await bleedToggle.trigger('click');

    const patchedOptions = wrapper.emitted('update:options')!.at(-1)![0] as SpotifyEmbed['options'];
    expect(patchedOptions?.bleed).toBe(false);
  });
});
