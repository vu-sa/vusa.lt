import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import MailQueue from '../MailQueue.vue';

import { commonStubs } from '@/tests/stubs';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import CollectionTitleBand from '@/Components/Collection/CollectionTitleBand.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useFeatureSpotlight', async () => {
  const { ref } = await import('vue');
  return { useFeatureSpotlight: () => ({ isDismissed: ref(true), dismiss: vi.fn() }) };
});
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
  props: ['rowActions', 'eyebrow', 'title', 'lead'],
  setup(props, { slots }) {
    return () => h('div', [
      h(CollectionTitleBand, { eyebrow: props.eyebrow, title: props.title, lead: props.lead }),
      slots.actions?.(),
      slots.row?.({ item: recipient, actions: props.rowActions(recipient), index: 0 }),
    ]);
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
    global: { stubs: { ...commonStubs, SpotlightPopover: { template: '<div><slot /></div>' }, CollectionPage: CollectionPageStub, ConfirmDialog: ConfirmDialogStub } },
  });
}

beforeEach(() => vi.clearAllMocks());

describe('MailQueue', () => {
  it('renders the collection title', () => {
    const wrapper = factory();
    const titleBand = wrapper.get('[data-slot="collection-title-band"]');

    expect(titleBand.get('h1').text()).toBe('Laiškų eilė');
  });

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
    await wrapper.get('[data-testid="dropdown-menu-content"] [data-action="discard"]').trigger('click');
    await wrapper.find('[data-testid="cancel-discard"]').trigger('click');

    expect(wrapper.find('[data-testid="discard-confirmation"]').exists()).toBe(false);
    expect(router.delete).not.toHaveBeenCalled();
  });

  it('confirms recipient discard through the shared menu before sending the request', async () => {
    const wrapper = factory();
    expect(wrapper.findComponent(CollectionRowActions).props('spotlight')).toBe(true);
    await wrapper.get('[data-testid="dropdown-menu-content"] [data-action="discard"]').trigger('click');
    expect(wrapper.find('[data-testid="discard-confirmation"]').exists()).toBe(true);
    expect(router.delete).not.toHaveBeenCalled();
    await wrapper.get('[data-testid="confirm-discard"]').trigger('click');
    expect(router.delete).toHaveBeenCalledWith(route('mailQueue.destroyForUser', recipient.user_id), expect.any(Object));
  });

  it('offers the real digest preview in a new tab for read-only viewers', () => {
    const wrapper = factory(false);
    const link = wrapper.get('a[data-action="preview"]');

    expect(link.attributes('href')).toBe(route('mailQueue.previewForUser', recipient.user_id));
    expect(link.attributes('target')).toBe('_blank');
    expect(router.delete).not.toHaveBeenCalled();
  });

  it('hides destructive actions from read-only viewers', () => {
    const wrapper = factory(false);
    expect(wrapper.find('button[aria-label="Pašalinti eilutę"]').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('Nesiųsti');
  });
});
