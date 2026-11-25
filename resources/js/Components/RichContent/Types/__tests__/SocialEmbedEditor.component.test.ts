import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { reactive } from 'vue';

import RCSocialEmbed from '../../RCSocialEmbed.vue';
import SocialEmbedEditor from '../SocialEmbedEditor.vue';

interface EmbedContent { url: string; platform: 'facebook' | 'instagram' | null }

describe('SocialEmbedEditor', () => {
  it('labels its controls and identifies a supported URL', async () => {
    const content = reactive<EmbedContent>({ url: '', platform: null });
    const wrapper = mount(SocialEmbedEditor, {
      props: { modelValue: content, options: { showCaption: true } },
      global: { stubs: { RCSocialEmbed: true } },
    });

    const url = wrapper.find('input[type="url"]');
    expect(wrapper.find(`label[for="${url.attributes('id')}"]`).exists()).toBe(true);
    const checkbox = wrapper.find('[role="checkbox"]');
    expect(wrapper.find(`label[for="${checkbox.attributes('id')}"]`).exists()).toBe(true);

    await url.setValue('https://www.instagram.com/p/example');
    expect(content.platform).toBe('instagram');
    expect(wrapper.text()).toContain('Nuoroda atpažinta');
  });

  describe('opening existing content', () => {
    it('synchronizes platform immediately on mount when platform was null and renders RCSocialEmbed preview', () => {
      const content = reactive<EmbedContent>({
        url: 'https://www.instagram.com/reel/example123/',
        platform: null,
      });
      const wrapper = mount(SocialEmbedEditor, {
        props: { modelValue: content, options: { showCaption: true } },
        global: { stubs: { RCSocialEmbed: true } },
      });

      expect(content.platform).toBe('instagram');
      expect(wrapper.text()).toContain('Instagram');
      expect(wrapper.text()).toContain('Nuoroda atpažinta');
      const preview = wrapper.findComponent(RCSocialEmbed);
      expect(preview.exists()).toBe(true);
      expect(preview.props('element')).toEqual({
        json_content: { url: 'https://www.instagram.com/reel/example123/', platform: null },
        options: { showCaption: true },
      });
    });

    it('corrects a mismatched/stale saved platform on mount', () => {
      const content = reactive<EmbedContent>({
        url: 'https://www.facebook.com/vustudentusatstovybe/posts/123456',
        platform: 'instagram',
      });
      const wrapper = mount(SocialEmbedEditor, {
        props: { modelValue: content, options: { showCaption: true } },
        global: { stubs: { RCSocialEmbed: true } },
      });

      expect(content.platform).toBe('facebook');
      expect(wrapper.text()).toContain('Facebook');
      expect(wrapper.text()).toContain('Nuoroda atpažinta');
    });

    it('clears platform on mount when url is empty but platform was saved', () => {
      const content = reactive<EmbedContent>({
        url: '',
        platform: 'facebook',
      });
      const wrapper = mount(SocialEmbedEditor, {
        props: { modelValue: content, options: { showCaption: true } },
        global: { stubs: { RCSocialEmbed: true } },
      });

      expect(content.platform).toBeNull();
      expect(wrapper.find('[data-testid="platform-badge"]').exists()).toBe(false);
      expect(wrapper.text()).not.toContain('Patikrink nuorodą');
    });
  });

  describe('switching platforms and clearing URL', () => {
    it('switches platform when user enters a different social platform URL', async () => {
      const content = reactive<EmbedContent>({
        url: 'https://www.facebook.com/vustudentusatstovybe/posts/123',
        platform: 'facebook',
      });
      const wrapper = mount(SocialEmbedEditor, {
        props: { modelValue: content, options: { showCaption: true } },
        global: { stubs: { RCSocialEmbed: true } },
      });

      expect(wrapper.find('[data-testid="platform-badge"]').text()).toContain('Facebook');

      const urlInput = wrapper.find('input[type="url"]');
      await urlInput.setValue('https://www.instagram.com/reels/C_example123/');

      expect(content.platform).toBe('instagram');
      expect(wrapper.find('[data-testid="platform-badge"]').text()).toContain('Instagram');
      expect(wrapper.find('[data-testid="platform-badge"]').text()).not.toContain('Facebook');

      await urlInput.setValue('https://m.facebook.com/posts/456');
      expect(content.platform).toBe('facebook');
      expect(wrapper.find('[data-testid="platform-badge"]').text()).toContain('Facebook');
    });

    it('clears platform and hides preview when URL is emptied', async () => {
      const content = reactive<EmbedContent>({
        url: 'https://www.instagram.com/p/example',
        platform: 'instagram',
      });
      const wrapper = mount(SocialEmbedEditor, {
        props: { modelValue: content, options: { showCaption: true } },
        global: { stubs: { RCSocialEmbed: true } },
      });

      const urlInput = wrapper.find('input[type="url"]');
      await urlInput.setValue('');

      expect(content.platform).toBeNull();
      expect(wrapper.text()).not.toContain('Nuoroda atpažinta');
      expect(wrapper.text()).not.toContain('Patikrink nuorodą');
      expect(wrapper.findComponent(RCSocialEmbed).exists()).toBe(false);
    });
  });

  describe('malformed URLs and lookalike domains', () => {
    it('shows warning and sets platform to null on malformed URL', async () => {
      const content = reactive<EmbedContent>({ url: '', platform: null });
      const wrapper = mount(SocialEmbedEditor, {
        props: { modelValue: content, options: { showCaption: true } },
        global: { stubs: { RCSocialEmbed: true } },
      });

      const urlInput = wrapper.find('input[type="url"]');
      await urlInput.setValue('not-a-valid-url');

      expect(content.platform).toBeNull();
      expect(wrapper.text()).toContain('Patikrink nuorodą');
      expect(wrapper.text()).not.toContain('Nuoroda atpažinta');
      expect(wrapper.findComponent(RCSocialEmbed).exists()).toBe(false);
    });

    it('shows warning and sets platform to null on lookalike domains', async () => {
      const content = reactive<EmbedContent>({ url: '', platform: null });
      const wrapper = mount(SocialEmbedEditor, {
        props: { modelValue: content, options: { showCaption: true } },
        global: { stubs: { RCSocialEmbed: true } },
      });

      const urlInput = wrapper.find('input[type="url"]');

      await urlInput.setValue('https://notfacebook.com/posts/123');
      expect(content.platform).toBeNull();
      expect(wrapper.text()).toContain('Patikrink nuorodą');
      expect(wrapper.findComponent(RCSocialEmbed).exists()).toBe(false);

      await urlInput.setValue('https://facebook.com.evil.com/posts/123');
      expect(content.platform).toBeNull();
      expect(wrapper.text()).toContain('Patikrink nuorodą');

      await urlInput.setValue('https://fakeinstagram.com/p/123');
      expect(content.platform).toBeNull();
      expect(wrapper.text()).toContain('Patikrink nuorodą');

      await urlInput.setValue('https://evil.com/?facebook.com');
      expect(content.platform).toBeNull();
      expect(wrapper.text()).toContain('Patikrink nuorodą');
    });

    it('shows warning on root domains without post target', async () => {
      const content = reactive<EmbedContent>({ url: '', platform: null });
      const wrapper = mount(SocialEmbedEditor, {
        props: { modelValue: content, options: { showCaption: true } },
        global: { stubs: { RCSocialEmbed: true } },
      });

      const urlInput = wrapper.find('input[type="url"]');
      await urlInput.setValue('https://www.facebook.com/');

      expect(content.platform).toBeNull();
      expect(wrapper.text()).toContain('Patikrink nuorodą');
    });
  });
});
