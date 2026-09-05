import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import MainNavigation from '../MainNavigation.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const chromeStubs = {
  AccessibilityMenu: { template: '<button type="button" />' },
  DarkModeSwitch: { template: '<button type="button" />' },
  HeaderWordmark: { template: '<span />' },
  MainMenu: { template: '<nav />' },
  MobileNavigation: { template: '<button type="button" />' },
  PadalinysSelector: { template: '<button type="button" />' },
  SearchButton: { template: '<button type="button" />' },
  SecondMenu: { template: '<nav />' },
  SmartLink: { template: '<a><slot /></a>' },
};

describe('MainNavigation.vue', () => {
  it('uses an opaque, unblurred header in high-contrast mode', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ app: { locale: 'lt' } }));

    const wrapper = mount(MainNavigation, {
      props: { isThemeDark: false },
      global: { stubs: chromeStubs },
    });

    const header = wrapper.find('header');

    // jsdom cannot resolve Tailwind's ancestor selector, so assert the high-contrast wiring.
    expect(header.classes()).toContain('[.a11y-contrast_&]:bg-background');
    expect(header.classes()).toContain('[.a11y-contrast_&]:backdrop-blur-none');
  });
});
