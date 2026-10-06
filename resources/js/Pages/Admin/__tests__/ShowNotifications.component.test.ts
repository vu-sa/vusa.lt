import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import ShowNotifications from '../ShowNotifications.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const CollectionPageStub = {
  name: 'CollectionPage',
  props: ['source', 'quickFilters'],
  emits: ['quickFilter'],
  template: '<div />',
};

const notifications = [
  { id: 'unread', type: 'test', data: { title: 'New' }, created_at: '2026-09-24', read_at: null },
  { id: 'read', type: 'test', data: { title: 'Old' }, created_at: '2026-09-23', read_at: '2026-09-24' },
];

afterEach(() => {
  window.history.replaceState({}, '', '/');
});

describe('notifications collection', () => {
  it('starts with unread notifications and can show all', () => {
    window.history.replaceState({}, '', '/mano/notifications');
    const wrapper = mount(ShowNotifications, {
      props: { notifications },
      global: { stubs: { ...commonStubs, CollectionPage: CollectionPageStub } },
    });
    const collection = wrapper.findComponent(CollectionPageStub);

    expect(collection.props('source').items.value.map((item: { id: string }) => item.id)).toEqual(['unread']);
    collection.vm.$emit('quickFilter', 'all');
    expect(collection.props('source').items.value).toHaveLength(2);
  });
});
