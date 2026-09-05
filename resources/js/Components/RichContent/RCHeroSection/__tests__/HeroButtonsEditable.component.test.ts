import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import type { Component } from 'vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import HeroButtonsEditable from '../HeroButtonsEditable.vue';
import { ACTIVE_HOTSPOT_KEY, useActiveHotspot } from '../../Editor/Fullscreen/useActiveHotspot';
import { commonStubs } from '@/tests/stubs';

const stubs = {
  ...commonStubs,
  HeroButtonHotspot: {
    props: ['button', 'index', 'blockKey'],
    emits: ['update:button', 'remove'],
    template: '<div class="hotspot-stub" :data-index="index">{{ button.text }}</div>',
  },
};

function mountEditable(overrides: Record<string, unknown> = {}) {
  const hotspots = useActiveHotspot();
  const wrapper = mount(HeroButtonsEditable, {
    props: {
      buttons: [],
      blockKey: 'hero-1',
      ...overrides,
    },
    global: { stubs, provide: { [ACTIVE_HOTSPOT_KEY]: hotspots } },
  });
  return { wrapper, hotspots };
}

describe('HeroButtonsEditable', () => {
  it('renders one hotspot per button', () => {
    const { wrapper } = mountEditable({ buttons: [{ text: 'A', link: '#a' }, { text: 'B', link: '#b' }] });

    expect(wrapper.findAll('.hotspot-stub')).toHaveLength(2);
  });

  it('keeps the published row alignment while the add affordance is overlaid', () => {
    const { wrapper } = mountEditable({
      buttons: [{ text: 'A', link: '#a' }],
      class: 'justify-center',
    });

    expect(wrapper.find('.flex').classes()).toContain('justify-center');
    expect(wrapper.find('button').classes()).toContain('absolute');
  });

  it('renders only the add placeholder when the array is empty', () => {
    const { wrapper } = mountEditable({ buttons: [] });
    expect(wrapper.findAll('.hotspot-stub')).toHaveLength(0);
    expect(wrapper.text()).toContain('rich-content.add_button');
  });

  it('clicking the add placeholder pushes a minimal button and opens its popover', async () => {
    const { wrapper, hotspots } = mountEditable({ buttons: [{ text: 'A', link: '#a' }] });

    await wrapper.find('button').trigger('click');

    const emitted = wrapper.emitted('update:buttons');
    expect(emitted).toBeTruthy();
    const next = emitted!.at(-1)![0] as { text: string; link: string; variant: string }[];
    expect(next).toHaveLength(2);
    expect(next[1]).toEqual({ text: '', link: '', variant: 'default' });

    await wrapper.vm.$nextTick();
    expect(hotspots.isPopoverOpen('hero-1:buttons:1')).toBe(true);
  });

  it('shows the banner hint text only for the banner variant', () => {
    const withBanner = mountEditable({ variant: 'banner' }).wrapper;
    expect(withBanner.text()).toContain('rich-content.hero_banner_buttons_hint');

    const withoutBanner = mountEditable({ variant: 'split' }).wrapper;
    expect(withoutBanner.text()).not.toContain('rich-content.hero_banner_buttons_hint');
  });

  it('hides the add placeholder once two buttons exist, and refuses a third', async () => {
    const { wrapper } = mountEditable({ buttons: [{ text: 'A', link: '#a' }, { text: 'B', link: '#b' }] });

    expect(wrapper.find('button').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('rich-content.add_button');
  });

  it('removes only the button whose popover requested removal', async () => {
    const { wrapper } = mountEditable({ buttons: [{ text: 'A', link: '#a' }, { text: 'B', link: '#b' }] });

    await wrapper.findAllComponents(stubs.HeroButtonHotspot as Component)[1]!.vm.$emit('remove');

    expect(wrapper.emitted('update:buttons')?.at(-1)).toEqual([[{ text: 'A', link: '#a' }]]);
  });
});
