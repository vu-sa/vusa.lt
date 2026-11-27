import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { h, nextTick } from 'vue';
import { i18nVue, loadLanguageAsync, reset } from 'laravel-vue-i18n';

import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/Components/ui/dialog';
import { Sheet, SheetContent, SheetDescription, SheetTitle } from '@/Components/ui/sheet';

import en from '../../../../../../lang/en.json';
import lt from '../../../../../../lang/lt.json';

vi.unmock('laravel-vue-i18n');
enableAutoUnmount(afterEach);

afterEach(reset);

describe('overlay close buttons', () => {
  it('updates the dialog and sheet close names when the app language changes', async () => {
    mount(() => h('div', [
      h(Dialog, { open: true, modal: false }, () => h(DialogContent, () => [
        h(DialogTitle, () => 'Dialog title'),
        h(DialogDescription, () => 'Dialog description'),
      ])),
      h(Sheet, { open: true, modal: false }, () => h(SheetContent, () => [
        h(SheetTitle, () => 'Sheet title'),
        h(SheetDescription, () => 'Sheet description'),
      ])),
    ]), {
      attachTo: document.body,
      global: { plugins: [[i18nVue, { lang: 'lt', resolve: (lang: string) => lang === 'en' ? en : lt }]] },
    });
    await loadLanguageAsync('lt');
    await flushPromises();
    const closeNames = () => ['dialog-content', 'sheet-content'].map(slot =>
      document.querySelector(`[data-slot="${slot}"] button`)?.textContent,
    );
    expect(closeNames()).toEqual(['Uždaryti', 'Uždaryti']);

    await loadLanguageAsync('en');
    await nextTick();

    expect(closeNames()).toEqual(['Close', 'Close']);
  });
});

describe('DialogContent', () => {
  it('puts attributes on the content element rather than dropping them at the portal', async () => {
    mount(() => h(Dialog, { open: true }, () => h(
      DialogContent,
      { 'data-testid': 'custom-dialog' },
      () => [h(DialogTitle, () => 'Title'), h(DialogDescription, () => 'Description')],
    )), { attachTo: document.body });
    await flushPromises();

    const content = document.querySelector('[data-testid="custom-dialog"]');
    expect(content).not.toBeNull();
    expect(content?.getAttribute('role')).toBe('dialog');
  });
});
