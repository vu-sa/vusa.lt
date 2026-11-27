import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import type { Hero } from '@/Types/contentParts';

import HeroButtons from '../HeroButtons.vue';

type HeroButton = NonNullable<Hero['json_content']['buttons']>[number];

function makeButton(overrides: Partial<HeroButton> = {}): HeroButton {
  return { text: 'Dalyvauk', link: '/lt/renginiai', variant: 'default', color: 'red', ...overrides } as HeroButton;
}

function classesOf(button: HeroButton, props: Record<string, unknown> = {}): string {
  const wrapper = mount(HeroButtons, {
    props: { buttons: [button], ...props },
    global: { stubs: { SmartLink: { props: ['href'], template: '<a :href="href"><slot /></a>' } } },
  });
  return wrapper.find('button').attributes('class') ?? '';
}

describe('HeroButtons', () => {
  it('uses the themed brand fill by default', () => {
    const classes = classesOf(makeButton());
    expect(classes).toContain('bg-brand-fill');
    expect(classes).not.toMatch(/\bdark:bg-/);
  });

  it('renders the hairline outline on light surfaces by default', () => {
    const classes = classesOf(makeButton({ variant: 'outline' }));
    expect(classes).toContain('border-(--button-outline-border)');
    expect(classes).toContain('text-foreground');
  });

  it('renders brand-outline-on-dark with white text and translucent border when onDark is true', () => {
    const classes = classesOf(makeButton({ variant: 'outline' }), { onDark: true });
    expect(classes).toContain('border-white/25');
    expect(classes).toContain('text-white');
    expect(classes).toContain('hover:text-brand');
    expect(classes).toContain('hover:border-brand');
  });

  it('activates brand-outline-on-dark when dark class is in props.class', () => {
    const classes = classesOf(makeButton({ variant: 'outline' }), { class: 'dark' });
    expect(classes).toContain('border-white/25');
    expect(classes).toContain('text-white');
  });

  it('renders nothing when there are no buttons', () => {
    const wrapper = mount(HeroButtons, { props: { buttons: [] } });

    expect(wrapper.find('button').exists()).toBe(false);
  });
});

it('keeps button labels visible without binding unsupported links', () => {
  const wrapper = mount(HeroButtons, {
    props: { buttons: [makeButton({ link: 'javascript:void(0)' })] },
  });

  expect(wrapper.text()).toContain('Dalyvauk');
  expect(wrapper.find('a').exists()).toBe(false);
});
