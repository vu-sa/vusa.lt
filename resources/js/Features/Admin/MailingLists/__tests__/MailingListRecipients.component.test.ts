import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import MailingListRecipients from '../MailingListRecipients.vue';
import type { MailingListDiffPayload, MailingListRosterEntry } from '../types';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const diff: MailingListDiffPayload = {
  add: [{ email: 'new@vusa.lt', display_name: 'Nauja', reasons: ['Taryba · Narys'] }],
  remove: [{ email: 'old@vusa.lt', display_name: 'Sena' }],
};

function mountRecipients(roster: MailingListRosterEntry[] = [], canUpdate = true) {
  return mount(MailingListRecipients, {
    props: { mailingListId: 'list-1', roster, diff, managed: true, canUpdate },
  });
}

beforeEach(() => vi.clearAllMocks());

describe('MailingListRecipients', () => {
  it('keeps a recipient Mailman has as a manual email source', async () => {
    await mountRecipients().get('[data-testid="diff-keep"]').trigger('click');

    expect(router.post).toHaveBeenCalledWith(
      expect.stringContaining('mailingLists.sources.store'),
      { type: 'email', email: 'old@vusa.lt', display_name: 'Sena', address_mode: 'holder' },
      expect.anything(),
    );
  });

  it('excludes a recipient the sources would add', async () => {
    await mountRecipients().get('[data-testid="diff-exclude"]').trigger('click');

    expect(router.post).toHaveBeenCalledWith(
      expect.stringContaining('mailingLists.exclusions.store'),
      { email: 'new@vusa.lt' },
      expect.anything(),
    );
  });

  it('shows why each recipient is on the list', () => {
    const wrapper = mountRecipients([
      { email: 'member@stud.vu.lt', display_name: 'Narys', reasons: ['Taryba · Narys · fallback', 'Senatas · Narys'] },
      { email: 'unknown@vusa.lt', display_name: null, reasons: null },
    ]);

    expect(wrapper.text()).toContain('Taryba · Narys · fallback · Senatas · Narys');
    expect(wrapper.text()).toContain('unknown@vusa.lt');
  });

  it('hides the Mailman differences from people who cannot change the list', () => {
    expect(mountRecipients([], false).find('[data-testid="mailing-list-diff"]').exists()).toBe(false);
  });
});
