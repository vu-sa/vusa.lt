import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

import type { SocialEmbed } from '@/Types/contentParts';

import RCSocialEmbed from '../RCSocialEmbed.vue';

function cleanupSdkGlobals(): void {
  delete window.FB;
  delete window.fbAsyncInit;
  delete window.instgrm;
  document.getElementById('facebook-jssdk')?.remove();
  document.getElementById('instagram-embed-js')?.remove();
}

describe('social embed', () => {
  beforeEach(() => {
    cleanupSdkGlobals();
  });

  afterEach(() => {
    vi.useRealTimers();
    cleanupSdkGlobals();
  });

  it.each(['https://example.org/post/1', 'http://www.facebook.com/posts/123', 'data:text/html,example'])('omits an unsupported source at %s', (url) => {
    const element: SocialEmbed = { json_content: { url, platform: 'facebook' }, options: {} };
    const wrapper = mount(RCSocialEmbed, { props: { element } });
    expect(wrapper.find('iframe, a, .fb-post, .instagram-media').exists()).toBe(false);
    wrapper.unmount();
  });

  it('shows the empty placeholder without a spinner when editable and url is blank', () => {
    const element: SocialEmbed = { json_content: { url: '', platform: null }, options: {} };
    const wrapper = mount(RCSocialEmbed, { props: { element, editable: true } });

    expect(wrapper.text()).toContain('Facebook / Instagram');
    expect(wrapper.find('.animate-spin').exists()).toBe(false);
    wrapper.unmount();
  });

  it('renders a Facebook embed and re-parses when url or showCaption changes', async () => {
    vi.useFakeTimers();
    const parse = vi.fn();
    window.FB = { init: vi.fn(), XFBML: { parse } };

    const element = reactive<SocialEmbed>({
      json_content: { url: 'https://www.facebook.com/vusa/posts/123', platform: null },
      options: { showCaption: true },
    });

    const wrapper = mount(RCSocialEmbed, { props: { element } });
    expect(wrapper.get('.fb-post').attributes('data-href')).toBe('https://www.facebook.com/vusa/posts/123');
    expect(wrapper.get('.fb-post').attributes('data-show-text')).toBe('true');

    await vi.advanceTimersByTimeAsync(200);
    await flushPromises();
    expect(parse).toHaveBeenCalledTimes(1);

    element.options.showCaption = false;
    await vi.advanceTimersByTimeAsync(200);
    await flushPromises();
    expect(wrapper.get('.fb-post').attributes('data-show-text')).toBe('false');
    expect(parse).toHaveBeenCalledTimes(2);

    element.json_content.url = 'https://www.facebook.com/vusa/posts/456';
    await vi.advanceTimersByTimeAsync(200);
    await flushPromises();
    expect(wrapper.get('.fb-post').attributes('data-href')).toBe('https://www.facebook.com/vusa/posts/456');
    expect(parse).toHaveBeenCalledTimes(3);
    wrapper.unmount();
  });

  it('renders an Instagram embed with normalized permalink and re-processes on url change', async () => {
    vi.useFakeTimers();
    const process = vi.fn();
    window.instgrm = { Embeds: { process } };

    const element = reactive<SocialEmbed>({
      json_content: { url: 'https://www.instagram.com/p/C_abc123/?igsh=track', platform: null },
      options: { showCaption: true },
    });

    const wrapper = mount(RCSocialEmbed, { props: { element } });
    expect(wrapper.get('.instagram-media').attributes('data-instgrm-permalink')).toBe('https://www.instagram.com/p/C_abc123/');

    await vi.advanceTimersByTimeAsync(200);
    await flushPromises();
    expect(process).toHaveBeenCalledTimes(1);

    element.json_content.url = 'https://www.instagram.com/reel/C_xyz789';
    await vi.advanceTimersByTimeAsync(200);
    await flushPromises();
    expect(wrapper.get('.instagram-media').attributes('data-instgrm-permalink')).toBe('https://www.instagram.com/reel/C_xyz789/');
    expect(process).toHaveBeenCalledTimes(2);
    wrapper.unmount();
  });
});
