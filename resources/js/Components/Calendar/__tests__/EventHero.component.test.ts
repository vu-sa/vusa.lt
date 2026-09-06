import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { h } from 'vue';
import { usePage } from '@inertiajs/vue3';

import EventHero from '@/Components/Calendar/EventHero.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
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

  it('renders the full-bleed editorial hero container', () => {
    const wrapper = mountHero();

    const hero = wrapper.find('[data-slot="event-hero"]');
    expect(hero.exists()).toBe(true);
    expect(hero.classes()).toContain('rc-viewport');
    expect(hero.classes()).toContain('border-b');
    expect(hero.classes()).toContain('border-border');
  });

  it('renders background image when provided', () => {
    const wrapper = mountHero({
      main_image_url: 'https://example.com/hero.jpg',
    });

    const hero = wrapper.find('[data-slot="event-hero"]');
    const img = hero.find('img');
    expect(img.exists()).toBe(true);
    expect(img.attributes('src')).toBe('https://example.com/hero.jpg');
  });

  it('positions the hero image at the event focal point', () => {
    const wrapper = mountHero({
      main_image_url: 'https://example.com/hero.jpg',
      main_image_focal_point: '40% 25%',
    });

    expect(wrapper.find('[data-slot="event-hero"] img').attributes('style')).toContain('object-position: 40% 25%');
  });

  it('renders date plate with day number and month', () => {
    const wrapper = mountHero({
      date: '2099-05-15T18:00:00+00:00',
    });

    const datePlate = wrapper.find('[data-slot="hero-date-plate"]');
    expect(datePlate.exists()).toBe(true);
    expect(datePlate.text()).toContain('15');
    expect(datePlate.text()).toContain('GEG');
  });

  it('renders category tag and title in the brand-rule container', () => {
    const wrapper = mountHero({
      category: { name: 'Konferencija' },
      title: 'Nuostabus Renginys',
    });

    const tag = wrapper.find('[data-slot="hero-tag"]');
    expect(tag.exists()).toBe(true);
    expect(tag.text()).toBe('Konferencija');

    const h1 = wrapper.find('h1');
    expect(h1.classes()).toContain('u-display');
    expect(h1.text()).toBe('Nuostabus Renginys');
  });

  it('renders hero actions via the actions slot', () => {
    const wrapper = mountHero();

    const actions = wrapper.find('.actions-stub');
    expect(actions.exists()).toBe(true);
    expect(actions.attributes('data-on-image')).toBe('true');
  });

  it('shows status badge for past events', () => {
    const wrapper = mountHero({
      date: '2020-01-01T18:00:00+00:00',
      end_date: '2020-01-01T20:00:00+00:00',
    });

    const status = wrapper.find('[data-slot="hero-status"]');
    expect(status.exists()).toBe(true);
    expect(status.text()).toContain('Renginys įvyko');
  });

  it('renders the minimal variant with band-masthead and no photo', () => {
    const wrapper = mountHero({
      hero_style: 'minimal',
      title: 'Posėdžio pranešimas',
    });

    const hero = wrapper.find('[data-slot="event-hero"]');
    expect(hero.exists()).toBe(true);
    expect(hero.classes()).toContain('band-masthead');
    expect(hero.find('img').exists()).toBe(false);
    expect(hero.text()).toContain('Posėdžio pranešimas');
    expect(wrapper.find('.actions-stub').attributes('data-on-image')).toBe('false');
  });

  it('renders the split variant with band-masthead and photo beside content', () => {
    const wrapper = mountHero({
      hero_style: 'split',
      main_image_url: 'https://example.com/split.jpg',
      title: 'Renginys su nuotrauka šalia',
    });

    const hero = wrapper.find('[data-slot="event-hero"]');
    expect(hero.exists()).toBe(true);
    expect(hero.classes()).toContain('band-masthead');
    expect(hero.find('.md\\:grid-cols-2').exists()).toBe(true);
    const img = hero.find('img');
    expect(img.exists()).toBe(true);
    expect(img.attributes('src')).toBe('https://example.com/split.jpg');
    expect(wrapper.find('.actions-stub').attributes('data-on-image')).toBe('false');
  });

  it('renders inline breadcrumbs inside the container', () => {
    const wrapper = mountHero();

    const breadcrumbs = wrapper.findComponent(PublicBreadcrumbs);
    expect(breadcrumbs.exists()).toBe(true);
    expect(breadcrumbs.props('variant')).toBe('inline');
  });
});
