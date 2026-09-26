import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import { h } from 'vue';

import { Dialog, DialogContent, DialogTitle } from '@/Components/ui/dialog';

afterEach(() => {
  document.body.innerHTML = '';
});

describe('DialogContent', () => {
  it('puts attributes on the content element rather than dropping them at the portal', async () => {
    mount(() => h(Dialog, { open: true }, () => h(
      DialogContent,
      { 'data-testid': 'custom-dialog' },
      () => h(DialogTitle, () => 'Title'),
    )), { attachTo: document.body });
    await flushPromises();

    const content = document.querySelector('[data-testid="custom-dialog"]');
    expect(content).not.toBeNull();
    expect(content?.getAttribute('role')).toBe('dialog');
  });
});
