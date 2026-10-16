import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import ShowMailingList from '@/Pages/Admin/MailingLists/ShowMailingList.vue';
import type { MailingListDiffPayload } from '@/Features/Admin/MailingLists/types';
import { MailingListMode } from '@/Types/enums';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const diff: MailingListDiffPayload = {
  add: [], remove: [{ email: 'old@vusa.lt', display_name: null }],
  needs_review: true, preview_token: 'review-1',
};

function mountPage(preview?: MailingListDiffPayload, can: { view?: boolean; update: boolean } = { update: true }) {
  return mount(ShowMailingList, {
    props: {
      mailingList: {
        id: 'list-1', address: 'test@konf.vusa.lt', display_name: 'Test', description: null,
        mode: MailingListMode.Unmanaged, tenant: { id: 1, shortname: 'VU SA' }, archive_policy: 'private',
        member_count: 1, posts_last_year: null, last_post_at: null, last_synced_at: null,
        last_sync_status: null, last_sync_error: null, archive_fetched_at: null,
        archive_complete: false, archive_imported: 0, archive_total: 0,
      },
      sources: [], exclusions: [], can, tenants: [], mailmanConfigured: true, diff: preview,
    },
    global: {
      stubs: {
        ...commonStubs,
        RecordPage: {
          name: 'RecordPage',
          props: ['primaryAction', 'overflowActions', 'sections', 'historySubject'], emits: ['action'],
          template: `<div><button v-if="primaryAction" data-testid="primary" :data-action="primaryAction.key" @click="$emit('action', primaryAction.key)">{{ primaryAction.label }}</button></div>`,
        },
        MailingListSheetForm: true,
        ConfirmDialog: {
          props: ['open', 'description'], emits: ['confirm', 'update:open'],
          template: `<div v-if="open" data-testid="review"><p>{{ description }}</p><button data-testid="confirm" @click="$emit('confirm')">Confirm</button></div>`,
        },
      },
    },
  });
}

beforeEach(() => vi.clearAllMocks());

describe('mailing list review', () => {
  it.each([undefined, { error: 'Unavailable' }])('requests a preview before offering activation', async (preview) => {
    const wrapper = mountPage(preview);
    expect(wrapper.find('[data-testid="primary"]').attributes('data-action')).toBe('preview');
    await wrapper.find('[data-testid="primary"]').trigger('click');
    expect(wrapper.find('[data-testid="review"]').exists()).toBe(false);
    expect(router.reload).toHaveBeenCalledWith(expect.objectContaining({ only: ['diff'] }));
    expect(router.patch).not.toHaveBeenCalled();
  });

  it('requires confirmation and submits the reviewed token only once while pending', async () => {
    const wrapper = mountPage(diff);
    await wrapper.find('[data-testid="primary"]').trigger('click');
    expect(router.patch).not.toHaveBeenCalled();
    expect(wrapper.find('[data-testid="review"]').exists()).toBe(true);
    await wrapper.find('[data-testid="confirm"]').trigger('click');
    await wrapper.find('[data-testid="confirm"]').trigger('click');
    expect(router.patch).toHaveBeenCalledTimes(1);
    expect(router.patch).toHaveBeenCalledWith(expect.any(String), { mode: 'managed', preview_token: 'review-1' },
      expect.objectContaining({ preserveScroll: true, except: ['messages'] }));
  });

  it('closes an approval when the preview changes', async () => {
    const wrapper = mountPage(diff);
    await wrapper.find('[data-testid="primary"]').trigger('click');
    await wrapper.setProps({ diff: { ...diff, preview_token: 'review-2' } });
    expect(wrapper.find('[data-testid="review"]').exists()).toBe(false);
    expect(router.patch).not.toHaveBeenCalled();
  });

  it('hides management actions, roster sections and history from an archive-only subscriber', () => {
    const wrapper = mountPage(diff, { view: false, update: false });
    expect(wrapper.find('[data-testid="primary"]').exists()).toBe(false);
    const recordPage = wrapper.findComponent({ name: 'RecordPage' });
    expect(recordPage.props('sections')).toEqual([expect.objectContaining({ value: 'archyvas' })]);
    expect(recordPage.props('historySubject')).toBeUndefined();
  });
});
