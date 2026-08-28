import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { h } from 'vue';
import { usePage } from '@inertiajs/vue3';

import EventHero from '@/Components/Calendar/EventHero.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

function makeEvent(overrides: Record<string, unknown> = {}) {
  return {
    id: 1,
    title: 'Test renginys',
    date: '2099-01-01T18:00:00+00:00',
    end_date: null,
    is_all_day: false,
    main_image_url: null,
    tenant: { shortname: 'VU SA' },
    category: { name: 'Konferencija' },
    ...overrides,
  };
}

const ActionsStub = {
  props: { onImage: { type: Boolean } },
  template: '<div class="actions-stub" :data-on-image="onImage" />',
};

function actionsSlot() {
  return {
    actions: (scope: { onImage: boolean }) => h(ActionsStub, { onImage: scope.onImage }),
  };
}

describe('Calendar/EventHero.vue', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage());
  });

  function mountHero(props: Record<string, unknown> = {}) {
    return mount(EventHero, {
      props: { event: makeEvent(props) },
      slots: actionsSlot(),
      global: { stubs: { ActionsStub } },
    });
  }

  it('defaults to the card variant when hero_style is missing', () => {
    const wrapper = mountHero();

    const hero = wrapper.find('[data-slot="event-hero"]');
    expect(hero.exists()).toBe(true);
    expect(hero.classes()).toContain('rounded-2xl');
    expect(hero.classes()).toContain('shadow-xl');
    expect(hero.classes()).toContain('ring-1');
  });

  it('renders the card variant with an overlaid image and on-image actions', () => {
    const wrapper = mountHero({
      hero_style: 'card',
      main_image_url: 'https://example.com/hero.jpg',
    });

    const hero = wrapper.find('[data-slot="event-hero"]');
    expect(hero.find('img').exists()).toBe(true);
    expect(hero.find('.actions-stub').exists()).toBe(true);
    expect(hero.find('.actions-stub').attributes('data-on-image')).toBe('true');
  });

  it('renders a gradient placeholder when the card variant has no image', () => {
    const wrapper = mountHero({ hero_style: 'card' });

    expect(wrapper.find('img').exists()).toBe(false);
    // Gradient placeholder div is present
    expect(wrapper.find('[data-slot="event-hero"] .bg-gradient-to-br').exists()).toBe(true);
  });

  it('renders the split variant as a two-column card with off-image actions', () => {
    const wrapper = mountHero({
      hero_style: 'split',
      main_image_url: 'https://example.com/hero.jpg',
    });

    const hero = wrapper.find('[data-slot="event-hero"]');
    expect(hero.classes()).toContain('md:grid');
    expect(hero.classes()).toContain('md:grid-cols-2');
    expect(hero.find('img').exists()).toBe(true);
    expect(hero.find('.actions-stub').attributes('data-on-image')).toBe('false');
  });

  it('renders the split variant without an image (content spans the card)', () => {
    const wrapper = mountHero({ hero_style: 'split' });

    const hero = wrapper.find('[data-slot="event-hero"]');
    expect(hero.find('img').exists()).toBe(false);
    // Content column widens to span both grid columns
    expect(hero.find('.md\\:col-span-2').exists()).toBe(true);
  });

  it('renders the minimal variant with no image and off-image actions', () => {
    const wrapper = mountHero({
      hero_style: 'minimal',
      main_image_url: 'https://example.com/hero.jpg',
    });

    const hero = wrapper.find('[data-slot="event-hero"]');
    expect(hero.classes()).toContain('space-y-3');
    expect(hero.find('img').exists()).toBe(false);
    expect(hero.find('.actions-stub').attributes('data-on-image')).toBe('false');
  });

  it('shows the tenant and category badges in every variant', () => {
    for (const heroStyle of ['card', 'split', 'minimal']) {
      const wrapper = mountHero({ hero_style: heroStyle });
      const hero = wrapper.find('[data-slot="event-hero"]');
      expect(hero.text()).toContain('VU SA');
      expect(hero.text()).toContain('Konferencija');
    }
  });
});
