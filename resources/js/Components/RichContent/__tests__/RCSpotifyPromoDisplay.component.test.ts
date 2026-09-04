import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const darkRef = ref(false);
vi.mock('@vueuse/core', async (importOriginal) => {
  const actual = await importOriginal() as Record<string, unknown>;
  return { ...actual, useDark: () => darkRef };
});

import RCSpotifyPromoDisplay from '../RCSpotifyPromoDisplay.vue';
import { createContentItem, getContentType } from '../Types';
import type { SpotifyEmbed } from '@/Types/contentParts';

function makeElement(overrides: Partial<SpotifyEmbed['json_content']> = {}, optionOverrides: SpotifyEmbed['options'] = {}): SpotifyEmbed {
  const item = createContentItem('spotify-embed');
  return {
    json_content: { ...item.json_content, ...overrides },
    options: { ...item.options, variant: 'promo', ...optionOverrides },
  } as SpotifyEmbed;
}

function mountDisplay(element: SpotifyEmbed, anchorId?: number) {
  return mount(RCSpotifyPromoDisplay, {
    props: { element, anchorId },
    global: { stubs: { SmartLink: { props: ['href'], template: '<a :href="href"><slot /></a>' } } },
  });
}

describe('RCSpotifyPromoDisplay', () => {
  it('spotify-embed registry entry stays self-consistent with the promo variant added', () => {
    const entry = getContentType('spotify-embed');
    expect(entry.value).toBe('spotify-embed');
    expect(() => entry.defaultContent()).not.toThrow();
    expect(() => entry.defaultOptions!()).not.toThrow();
    expect(entry.defaultOptions!()).toEqual({ variant: 'inline' });
  });

  it('renders the title and does not throw with no url', () => {
    darkRef.value = false;
    const wrapper = mountDisplay(makeElement({ title: 'Studentų garso banga' }));

    expect(wrapper.text()).toContain('Studentų garso banga');
    expect(wrapper.find('iframe').exists()).toBe(false);
  });

  it('renders a Spotify iframe with the light theme param', () => {
    darkRef.value = false;
    const wrapper = mountDisplay(makeElement({ url: 'https://open.spotify.com/show/abc' }));

    const iframe = wrapper.find('iframe');
    expect(iframe.exists()).toBe(true);
    expect(iframe.attributes('src')).toBe('https://open.spotify.com/show/abc?theme=1');
    expect(iframe.attributes('title')).toBe('Spotify Embed');
  });

  it('renders a Mixcloud widget iframe instead of the Spotify one for a mixcloud.com URL', () => {
    darkRef.value = true;
    const wrapper = mountDisplay(makeElement({ url: 'https://www.mixcloud.com/startfm/tiesiogiai-is-vu-sa/' }));

    const iframe = wrapper.find('iframe');
    expect(iframe.attributes('src')).toContain('player-widget.mixcloud.com');
    expect(iframe.attributes('src')).toContain('light=0');
    expect(iframe.attributes('title')).toBe('Mixcloud Embed');
  });

  it('bleeds to the viewport with a top/bottom rule by default, the same "new section" treatment EventCalendarElement uses', () => {
    const wrapper = mountDisplay(makeElement());

    const section = wrapper.find('section');
    expect(section.classes()).toContain('rc-viewport');
    expect(section.classes()).toContain('border-y');
  });

  // `.rc-band` is what lets two adjacent full-bleed bands (this, EventCalendarElement,
  // CtaBandDisplay) collapse their touching borders into one hairline instead of a doubled
  // line — see the `.rc-canvas>*:has(>.rc-band)+*:has(>.rc-band)>.rc-band` rule in canvas.css.
  // Losing this class from the bordered root silently brings the doubled-border bug back.
  it('marks the bordered root with .rc-band so adjacent bands can collapse their shared border', () => {
    const wrapper = mountDisplay(makeElement());

    expect(wrapper.find('section').classes()).toContain('rc-band');
  });

  it('drops the rc-viewport band when bleed is explicitly disabled', () => {
    const wrapper = mountDisplay(makeElement({}, { bleed: false }));

    const section = wrapper.find('section');
    expect(section.classes()).not.toContain('rc-viewport');
    expect(section.classes()).not.toContain('rc-band');
  });

  it('puts the embed panel first when textLeft is false', () => {
    const wrapper = mountDisplay(makeElement({}, { textLeft: false }));

    const panel = wrapper.find('.bg-ink');
    expect(panel.classes()).toContain('order-first');
  });

  it('renders a stable anchor id from anchorId', () => {
    const wrapper = mountDisplay(makeElement(), 42);

    expect(wrapper.find('#rc-42').exists()).toBe(true);
  });
});
