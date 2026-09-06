import { describe, it, expect, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';

import PublicBreadcrumbs from '../Public/PublicBreadcrumbs.vue';

import { createBreadcrumbState } from '@/Composables/useBreadcrumbsUnified';

// Mock the import.meta.env for development mode
vi.stubGlobal('import', {
  meta: {
    env: {
      DEV: true,
    },
  },
});

describe('PublicBreadcrumbs Fallback Mode', () => {
  it('displays warning in development when no breadcrumb state is provided', () => {
    // Spy on console.warn to check if the warning is logged
    const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

    // Mount component without providing breadcrumb state
    const wrapper = mount(PublicBreadcrumbs);

    // Should show the development warning banner
    expect(wrapper.find('[data-testid="fallback-warning"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Breadcrumbs in fallback mode');

    // Should have logged the warning
    expect(warnSpy).toHaveBeenCalledWith(
      expect.stringContaining('🍞 [Breadcrumbs] State not provided'),
    );

    warnSpy.mockRestore();
  });

  it('does not break when breadcrumb functions are called in fallback mode', () => {
    const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

    // This should not throw an error even without state provider
    expect(() => {
      mount(PublicBreadcrumbs);
    }).not.toThrow();

    warnSpy.mockRestore();
  });
});

/**
 * A host that provides real breadcrumb state, so the breadcrumbs can be rendered as pages
 * render them. `createBreadcrumbState` uses provide/inject, hence the wrapper component.
 */
function renderWithTrail(variant?: 'inline') {
  const Host = defineComponent({
    setup() {
      const state = createBreadcrumbState('public');
      state.set([
        { label: 'Pradinis', href: '/lt' },
        { label: 'Naujienos', href: '/lt/naujienos' },
        { label: 'Straipsnis' },
      ]);

      return () => h(PublicBreadcrumbs, variant ? { variant } : {});
    },
  });

  return mount(Host);
}

describe('PublicBreadcrumbs rendering', () => {
  it('renders unboxed, slash-separated and iconless', () => {
    const nav = renderWithTrail().find('nav');

    expect(nav.classes()).not.toContain('border-y');
    expect(nav.classes()).toContain('text-xs');
    expect(nav.text()).toContain('/');
    expect(nav.findAll('svg')).toHaveLength(0);
  });

  it('hovers to the brand colour, not to a hardcoded red', () => {
    const link = renderWithTrail().find('nav a');

    expect(link.classes()).toContain('hover:text-brand');
    expect(link.classes().join(' ')).not.toContain('vusa-red');
  });
});
