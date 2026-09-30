import { createInertiaApp } from '@inertiajs/vue3';
import type { Page } from '@inertiajs/core';
import createServer from '@inertiajs/vue3/server';
import { createSSRApp, h, type DefineComponent } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { i18nVue, reset } from 'laravel-vue-i18n';
import { ZiggyVue, route, type Config } from 'ziggy-js';

import PublicLayout from './Layouts/PersistentPublicLayout.vue';
import { VILNIUS_TIMEZONE } from './Utils/dateTime';

// Render dates the way visitors' browsers (and Laravel's app.timezone) do; in UTC, anything near
// midnight landed on the previous day and was rewritten during hydration. Zone-less strings such
// as the calendar's "Y-m-d H:i" are read as local time, so this beats a `timeZone` on formatters.
process.env.TZ = VILNIUS_TIMEZONE;

const pages = import.meta.glob<{ default: DefineComponent }>('./Pages/Public/{HomePage,ContentPage,NewsPage}.vue');
const translations = import.meta.glob<{ default: Record<string, string> }>([
  '../../lang/lt.json', '../../lang/en.json', '../../lang/php_public_*.json',
], { eager: true });

// Mirrors Laravel's `inertia.ssr.timeout`: past it, Laravel has already fallen back to client rendering.
const budgetMs = Number(process.env.INERTIA_SSR_TIMEOUT ?? 1) * 1000;

function withinBudget<T>(work: Promise<T>): Promise<T> {
  let timer: ReturnType<typeof setTimeout> | undefined;
  const expired = new Promise<never>((_, reject) => {
    timer = setTimeout(() => reject(new Error(`SSR render exceeded ${budgetMs} ms`)), budgetMs);
  });
  return Promise.race([work, expired]).finally(() => clearTimeout(timer));
}

// Inertia and imported translation helpers share process state; serialize renders across locales.
// Each render is capped so one that never settles cannot stall the queue, and renders Laravel has
// already abandoned are dropped so a burst cannot build a backlog.
let pending = Promise.resolve();
const render = createServer((page) => {
  const queuedAt = Date.now();
  const result = pending.then(() => {
    if (Date.now() - queuedAt >= budgetMs) {
      throw new Error('SSR render dropped: waited past the Laravel timeout');
    }
    return withinBudget(renderPage(page));
  });
  pending = result.then(() => undefined, () => undefined);
  return result;
}, { host: '127.0.0.1', port: Number(process.env.INERTIA_SSR_PORT ?? 13714), cluster: false });

async function renderPage(page: Page) {
  if (page.props.auth || !pages[`./Pages/${page.component}.vue`]) {
    throw new Error('SSR accepts anonymous public pilot pages only');
  }
  reset();
  const ziggy = page.props.ziggy as Config;
  Object.assign(globalThis, {
    route: (name: string, params?: Parameters<typeof route>[1], absolute?: boolean) => route(name, params, absolute, ziggy),
  });
  const locale = (page.props.app as { locale?: string })?.locale === 'en' ? 'en' : 'lt';
  const renderErrors: unknown[] = [];
  return createInertiaApp({
    page,
    serverHead: true,
    render: async (app) => {
      const html = await renderToString(app);
      if (renderErrors.length) throw renderErrors[0];
      return html;
    },
    resolve: async (name) => {
      const module = await pages[`./Pages/${name}.vue`]!();
      module.default.layout = PublicLayout;
      return module;
    },
    setup: ({ App, props, plugin }) => {
      const app = createSSRApp({ render: () => h(App, props) })
        .use(plugin)
        .use(i18nVue, {
          lang: locale,
          fallbackLang: 'en',
          resolve: (lang: string) => ({
            ...translations[`../../lang/${lang}.json`]?.default,
            ...translations[`../../lang/php_public_${lang}.json`]?.default,
          }),
        })
        .use(ZiggyVue, ziggy);
      app.config.errorHandler = (error) => {
        renderErrors.push(error);
      };
      return app;
    },
  });
}

export default render;
