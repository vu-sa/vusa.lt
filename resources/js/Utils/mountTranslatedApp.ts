import type { App } from 'vue';
import { loadLanguageAsync } from 'laravel-vue-i18n';

export async function mountTranslatedApp(application: App, el: HTMLElement, locale: string): Promise<void> {
  await loadLanguageAsync(locale);
  application.mount(el);
}
