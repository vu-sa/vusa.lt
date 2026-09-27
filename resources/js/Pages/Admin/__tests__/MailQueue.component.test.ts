import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import MailQueue from '../MailQueue.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useCollectionSource', () => ({
  useDatabaseCollectionSource: () => ({ refresh: vi.fn() }),
}));

const recipient = {
  user_id: 'user-1',
  user: { id: 'user-1', name: 'Test User', email: 'test@example.com', profile_photo_path: null },
  items_count: 1,
  oldest_at: '2026-09-27T10:00:00Z',
  newest_at: '2026-09-27T10:00:00Z',
  items: [{ id: 17, category: 'task', notification_class: 'Test', title: 'Task', body: 'Body', url: null, created_at: '2026-09-27T10:00:00Z' }],
};

const CollectionPageStub = defineComponent({
  setup(_, { slots }) {
    return () => h('div', [slots.actions?.(), slots.row?.({ item: recipient })]);
  },
});

const ConfirmDialogStub = {
  props: ['open', 'title', 'description'],
  template: '<div v-if="open" data-testid="discard-confirmation"><span>{{ title }}</span><button type="button" data-testid="confirm-discard" @click="$emit(\'confirm\')">Confirm</button><button type="button" data-testid="cancel-discard" @click="$emit(\'update:open\', false)">Cancel</button></div>',
};

function factory(canManage = true) {
  return mount(MailQueue, {
    props: {
      canManage,
      recipients: { data: [recipient], current_page: 1, last_page: 1, total: 1, per_page: 50 },
      totals: { items: 1, recipients: 1 },
    },
    global: { stubs: { CollectionPage: CollectionPageStub, ConfirmDialog: ConfirmDialogStub } },
  });
}

beforeEach(() => vi.clearAllMocks());

describe('MailQueue', () => {
  it('requires confirmation before discarding one notification', async () => {
    const wrapper = factory();
    await wrapper.find('button[aria-label="Pašalinti eilutę"]').trigger('click');

    expect(wrapper.find('[data-testid="discard-confirmation"]').exists()).toBe(true);
    expect(router.delete).not.toHaveBeenCalled();

    await wrapper.find('[data-testid="confirm-discard"]').trigger('click');
    expect(router.delete).toHaveBeenCalledWith(route('mailQueue.destroy', 17), expect.any(Object));
  });

  it('can cancel the recipient discard without a request', async () => {
    const wrapper = factory();
    const recipientButton = wrapper.findAll('button').find(button => button.text().includes('Nesiųsti'));
    expect(recipientButton).toBeDefined();
    await recipientButton?.trigger('click');
    await wrapper.find('[data-testid="cancel-discard"]').trigger('click');

    expect(wrapper.find('[data-testid="discard-confirmation"]').exists()).toBe(false);
    expect(router.delete).not.toHaveBeenCalled();
  });

  it('hides destructive actions from read-only viewers', () => {
    const wrapper = factory(false);
    expect(wrapper.find('button[aria-label="Pašalinti eilutę"]').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('Nesiųsti');
  });
});
