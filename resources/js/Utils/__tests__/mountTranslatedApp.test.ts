import { afterEach, describe, expect, it, vi } from 'vitest';
import { createApp, createSSRApp } from 'vue';
import { i18nVue, reset } from 'laravel-vue-i18n';

import { mountTranslatedApp } from '../mountTranslatedApp';

vi.unmock('laravel-vue-i18n');

afterEach(() => reset());

describe('mountTranslatedApp', () => {
  it.each([
    ['en', false, 'Units'],
    ['lt', false, 'Padaliniai'],
    ['en', true, 'Units'],
  ] as const)('waits for %s translations before mounting (hydrating: %s)', async (locale, hydrating, label) => {
    let release!: (messages: { default: Record<string, string> }) => void;
    const messages = new Promise<{ default: Record<string, string> }>((resolve) => {
      release = resolve;
    });
    const application = (hydrating ? createSSRApp : createApp)({ template: '<button>{{ $t("Padaliniai") }}</button>' })
      .use(i18nVue, { lang: locale, resolve: () => messages });
    const container = document.createElement('div');
    if (hydrating) container.innerHTML = `<button>${label}</button>`;
    const initialHTML = container.innerHTML;
    const warnings = vi.spyOn(console, 'warn');

    const mounted = mountTranslatedApp(application, container, locale);
    await Promise.resolve();
    expect(container.innerHTML).toBe(initialHTML);

    release({ default: { Padaliniai: label } });
    await mounted;

    expect(container.textContent).toBe(label);
    expect(warnings.mock.calls.filter(args => /hydration/i.test(args.join(' ')))).toEqual([]);
    application.unmount();
    warnings.mockRestore();
  });
});
