import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import SocialEmbedBlockToolbar from '../SocialEmbedBlockToolbar.vue';
import type { ContentPart } from '../../Types';

function makeContent(overrides: Partial<ContentPart> = {}): ContentPart {
  return {
    type: 'social-embed',
    json_content: { url: 'https://www.facebook.com/vustudentusatstovybe/posts/123', platform: 'facebook' },
    options: { showCaption: true },
    ...overrides,
  };
}

describe('SocialEmbedBlockToolbar', () => {
  it('renders url input and detected platform badge', () => {
    const wrapper = mount(SocialEmbedBlockToolbar, {
      props: {
        content: makeContent(),
        blockKey: 'social-1',
        canMoveUp: true,
        canMoveDown: true,
        canDelete: true,
      },
      global: {
        stubs: {
          RCBlockToolbarShell: { template: '<div><slot /></div>' },
          RCWidthPicker: true,
        },
      },
    });

    const input = wrapper.find('input[type="url"]');
    expect(input.exists()).toBe(true);
    expect(wrapper.text()).toContain('Facebook');
  });

  it('emits update:content when changing url', async () => {
    const wrapper = mount(SocialEmbedBlockToolbar, {
      props: {
        content: makeContent(),
        blockKey: 'social-1',
        canMoveUp: true,
        canMoveDown: true,
        canDelete: true,
      },
      global: {
        stubs: {
          RCBlockToolbarShell: { template: '<div><slot /></div>' },
          RCWidthPicker: true,
        },
      },
    });

    const input = wrapper.find('input[type="url"]');
    await input.setValue('https://www.instagram.com/p/abc123xyz/');

    expect(wrapper.emitted('update:content')).toBeTruthy();
    const emitted = wrapper.emitted('update:content')?.[0]?.[0] as ContentPart;
    expect((emitted.json_content as Record<string, unknown>).url).toBe('https://www.instagram.com/p/abc123xyz/');
    expect((emitted.json_content as Record<string, unknown>).platform).toBe('instagram');
  });
});
