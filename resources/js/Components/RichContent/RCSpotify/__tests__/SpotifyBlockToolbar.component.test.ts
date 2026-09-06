import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import SpotifyBlockToolbar from '../SpotifyBlockToolbar.vue';
import type { ContentPart } from '../../Types';

function makeContent(overrides: Partial<ContentPart> = {}): ContentPart {
  return {
    type: 'spotify-embed',
    json_content: { url: 'https://open.spotify.com/track/123' },
    options: { variant: 'inline' },
    ...overrides,
  };
}

describe('SpotifyBlockToolbar', () => {
  it('renders url input with value', () => {
    const wrapper = mount(SpotifyBlockToolbar, {
      props: {
        content: makeContent(),
        blockKey: 'spotify-1',
        canMoveUp: true,
        canMoveDown: true,
        canDelete: true,
      },
      global: {
        stubs: {
          RCBlockToolbarShell: { template: '<div><slot /></div>' },
          RCWidthPicker: true,
          RCPresentationPicker: true,
          TiptapImageButton: true,
        },
      },
    });

    const input = wrapper.find('input[type="url"]');
    expect(input.exists()).toBe(true);
    expect((input.element as HTMLInputElement).value).toBe('https://open.spotify.com/track/123');
  });

  it('emits update:content when changing url', async () => {
    const wrapper = mount(SpotifyBlockToolbar, {
      props: {
        content: makeContent(),
        blockKey: 'spotify-1',
        canMoveUp: true,
        canMoveDown: true,
        canDelete: true,
      },
      global: {
        stubs: {
          RCBlockToolbarShell: { template: '<div><slot /></div>' },
          RCWidthPicker: true,
          RCPresentationPicker: true,
          TiptapImageButton: true,
        },
      },
    });

    const input = wrapper.find('input[type="url"]');
    await input.setValue('https://open.spotify.com/track/456');

    expect(wrapper.emitted('update:content')).toBeTruthy();
    const emitted = wrapper.emitted('update:content')?.[0]?.[0] as ContentPart;
    expect((emitted.json_content as Record<string, unknown>).url).toBe('https://open.spotify.com/track/456');
  });

  it('renders promo-specific controls when variant is promo', () => {
    const wrapper = mount(SpotifyBlockToolbar, {
      props: {
        content: makeContent({ options: { variant: 'promo' } }),
        blockKey: 'spotify-1',
        canMoveUp: true,
        canMoveDown: true,
        canDelete: true,
      },
      global: {
        stubs: {
          RCBlockToolbarShell: { template: '<div><slot /></div>' },
          RCWidthPicker: true,
          RCPresentationPicker: true,
          TiptapImageButton: true,
        },
      },
    });

    expect(wrapper.text()).toContain('rich-content.player_on_right');
    expect(wrapper.text()).toContain('rich-content.panel_image');
  });
});
