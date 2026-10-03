import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import OverviewSection from '@/Components/Patterns/OverviewSection.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const headerOf = (props: Record<string, unknown>) => mount(OverviewSection, {
  props: { title: 'Mano rezervacijos', variant: 'home', ...props },
  slots: { default: '<ul class="border-y" />' },
}).get('header');

describe('OverviewSection', () => {
  it('rules the heading off from its content by default', () => {
    expect(headerOf({}).classes()).toContain('border-b');
  });

  it('drops the rule when the content draws its own, so no line doubles', () => {
    expect(headerOf({ contentRuled: true }).classes()).not.toContain('border-b');
  });
});
