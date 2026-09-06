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
});
