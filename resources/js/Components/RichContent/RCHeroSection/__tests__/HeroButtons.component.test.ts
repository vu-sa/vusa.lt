import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import HeroButtons from '../HeroButtons.vue';

import type { Hero } from '@/Types/contentParts';

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
  it('maps the default red to the brand fill rather than a fixed hex', () => {
    expect(classesOf(makeButton())).toContain('bg-brand-fill');
  });

  it('falls back to brand for an unknown authored colour', () => {
    expect(classesOf(makeButton({ color: 'chartreuse' as HeroButton['color'] }))).toContain('bg-brand-fill');
  });

  it('renders standard brand-outline on light surfaces by default', () => {
    const classes = classesOf(makeButton({ variant: 'outline' }));
    expect(classes).toContain('border-border');
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

  /**
   * The regression this guards: the shadcn variants these classes override ship
   * `dark:bg-zinc-50`-style rules, and a `dark:`-prefixed class wins over an unprefixed one
   * whatever the source order — so an override without its own `dark:` twin renders the
   * variant's colour in dark mode instead of the authored one. jsdom cannot evaluate which
   * rule wins, so this asserts the twin is present rather than the resulting colour.
   */
  it.each(['red', 'yellow', 'zinc', 'white'] as const)('repeats the %s fill under dark: so it survives the variant', (color) => {
    const classes = classesOf(makeButton({ color }));

    expect(classes).toMatch(/\bdark:(bg|text)-/);
  });

  it('renders nothing when there are no buttons', () => {
    const wrapper = mount(HeroButtons, { props: { buttons: [] } });

    expect(wrapper.find('button').exists()).toBe(false);
  });
});
