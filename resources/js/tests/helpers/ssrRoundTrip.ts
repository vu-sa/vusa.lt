import { config } from '@vue/test-utils';
import { createSSRApp, h, type Component } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { vi } from 'vitest';

/**
 * Server-renders a component, then hydrates that markup the way `public.ts` does. Returns the
 * server HTML, the hydrated container, and every Vue hydration warning raised on the way.
 *
 * `beforeHydrate` sets up browser-only state (localStorage, …) the server must not see.
 */
export async function ssrRoundTrip(
  component: Component,
  props: Record<string, unknown> = {},
  { beforeHydrate }: { beforeHydrate?: () => void } = {},
) {
  const createApp = () => {
    const app = createSSRApp({ render: () => h(component, props) });
    Object.assign(app.config.globalProperties, config.global.mocks);
    return app;
  };

  const html = await renderToString(createApp());

  // The same root the Laravel view emits, which client code may inspect while hydrating.
  const container = document.createElement('div');
  container.id = 'app';
  container.dataset.serverRendered = 'true';
  container.innerHTML = html;
  document.body.appendChild(container);
  beforeHydrate?.();

  const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});
  const error = vi.spyOn(console, 'error').mockImplementation(() => {});
  const app = createApp();
  app.mount(container);

  const hydrationWarnings = [...warn.mock.calls, ...error.mock.calls]
    .map(args => args.map(String).join(' '))
    .filter(message => /hydration/i.test(message));
  warn.mockRestore();
  error.mockRestore();

  const unmount = () => {
    app.unmount();
    container.remove();
  };

  return { html, container, hydrationWarnings, unmount };
}
