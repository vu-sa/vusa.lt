import { describe, expect, it } from 'vitest';
import { createSSRApp, defineComponent, h } from 'vue';
import { renderToString } from 'vue/server-renderer';

import { createBreadcrumbState, usePageBreadcrumbs } from '../useBreadcrumbsUnified';

import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';

/** Renders layout → page → trail the way the SSR server does: no mount hooks ever run. */
function renderPage(trail: string[]) {
  const Page = defineComponent({
    setup() {
      usePageBreadcrumbs(() => trail.map(label => ({ label })));

      return () => h(PublicBreadcrumbs);
    },
  });

  const Layout = defineComponent({
    setup() {
      createBreadcrumbState('public');

      return () => h(Page);
    },
  });

  return renderToString(createSSRApp(Layout));
}

describe('breadcrumbs in server rendering', () => {
  it('puts the trail into the server HTML', async () => {
    const html = await renderPage(['Naujienos', 'Straipsnis']);

    expect(html).toContain('data-slot="public-breadcrumbs"');
    expect(html).toContain('Straipsnis');
  });

  it('does not carry one render\'s trail into the next', async () => {
    await renderPage(['Naujienos', 'Kito lankytojo straipsnis']);

    const html = await renderPage([]);

    expect(html).not.toContain('Kito lankytojo straipsnis');
    expect(html).not.toContain('data-slot="public-breadcrumbs"');
  });
});
