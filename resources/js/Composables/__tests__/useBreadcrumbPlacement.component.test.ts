import { describe, expect, it } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';

import { createBreadcrumbState, usePageBreadcrumbs } from '../useBreadcrumbsUnified';

/**
 * Breadcrumb *placement*: `layout` (PublicLayout's own bar) or `band` (the page draws the trail
 * inside its title band). It exists so a detail page shows one trail rather than two.
 *
 * The state is module-level, so the ordering here matters — each test mounts a fresh host that
 * writes, then reads back through the provided state.
 */
function mountPage(placement?: 'layout' | 'band', label = 'Straipsnis') {
  // The page must be a *child* of the provider: `createBreadcrumbState` provides, and Vue's
  // `inject` reads the parent chain — injecting in the same component that provided falls into
  // the composable's fallback mode, which sets nothing at all. This mirrors the real tree, where
  // PublicLayout provides and the page component consumes.
  const Page = defineComponent({
    setup() {
      usePageBreadcrumbs(() => [{ label }], placement ? { placement } : {});

      return () => h('div');
    },
  });

  let state!: ReturnType<typeof createBreadcrumbState>;

  const Layout = defineComponent({
    setup() {
      state = createBreadcrumbState('public');

      return () => h(Page);
    },
  });

  const wrapper = mount(Layout);

  return { wrapper, state };
}

describe('breadcrumb placement', () => {
  it('defaults to the layout bar', () => {
    const { state } = mountPage();

    expect(state.placement.value).toBe('layout');
    expect(state.breadcrumbs.value.length).toBeGreaterThan(0);
  });

  it('lets a page claim the trail for its own title band', () => {
    const { state } = mountPage('band');

    expect(state.placement.value).toBe('band');
  });

  /**
   * The reason `set()` takes placement rather than exposing a separate setter: breadcrumbs
   * persist across navigation, so a page that says nothing about placement must reset it. Without
   * this, every page visited after an article would hide its own trail and show none at all.
   */
  it('resets to the layout bar for the next page that says nothing about it', () => {
    mountPage('band');

    const { state } = mountPage(undefined, 'Kitas puslapis');

    expect(state.placement.value).toBe('layout');
  });

  it('resets to the layout bar when the trail is cleared', () => {
    const { state } = mountPage('band');

    state.clear();

    expect(state.placement.value).toBe('layout');
    expect(state.breadcrumbs.value).toHaveLength(0);
  });
});
