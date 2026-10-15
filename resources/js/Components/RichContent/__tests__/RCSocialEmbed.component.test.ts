import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import RCSocialEmbed from '../RCSocialEmbed.vue';

import type { SocialEmbed } from '@/Types/contentParts';

function makeElement(overrides: Partial<SocialEmbed['json_content']> = {}, options: SocialEmbed['options'] = null): SocialEmbed {
  return { json_content: { url: '', platform: null, ...overrides }, options };
}

describe('RCSocialEmbed', () => {
  it('renders a placeholder when editable is true and url is empty', () => {
    const wrapper = mount(RCSocialEmbed, {
      props: {
        element: makeElement({ url: '' }),
        editable: true,
        blockKey: 'social-1',
      },
    });

    expect(wrapper.text()).toContain('rich-content.enter_social_url');
  });

  it('renders fallback link when url is invalid and not loading', async () => {
    const wrapper = mount(RCSocialEmbed, {
      props: {
        element: makeElement({ url: 'https://example.com/some-post' }),
      },
    });

    expect(wrapper.text()).toContain('Atidaryti įrašą naujame lange');
  });

  it('renders fallback link for lookalike domain even if platform was stored as facebook', () => {
    const wrapper = mount(RCSocialEmbed, {
      props: {
        element: makeElement({
          url: 'https://notfacebook.com/posts/123',
          platform: 'facebook',
        }),
      },
    });

    expect(wrapper.find('.facebook-embed').exists()).toBe(false);
    expect(wrapper.text()).toContain('Atidaryti įrašą naujame lange');
  });

  it('renders instagram embed blockquote for reel url', () => {
    const wrapper = mount(RCSocialEmbed, {
      props: {
        element: makeElement({
          url: 'https://www.instagram.com/reels/C_example123/?igsh=123',
        }),
      },
    });

    const ig = wrapper.find('.instagram-embed');
    expect(ig.exists()).toBe(true);
    const blockquote = ig.find('blockquote');
    expect(blockquote.attributes('data-instgrm-permalink')).toBe('https://www.instagram.com/reels/C_example123/');
  });

  it('renders facebook embed for supported facebook url', () => {
    const wrapper = mount(RCSocialEmbed, {
      props: {
        element: makeElement({
          url: 'https://www.facebook.com/vustudentusatstovybe/posts/123456',
        }),
      },
    });

    const fb = wrapper.find('.facebook-embed');
    expect(fb.exists()).toBe(true);
    expect(fb.find('.fb-post').attributes('data-href')).toBe('https://www.facebook.com/vustudentusatstovybe/posts/123456');
  });
});
