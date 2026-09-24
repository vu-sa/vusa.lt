import { mount, flushPromises } from '@vue/test-utils';
import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { commonStubs } from '@/tests/stubs';

const fetchThread = vi.fn();
const fetchMentionables = vi.fn();
const mockFetch = vi.fn();
vi.stubGlobal('fetch', mockFetch);

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.mock('@/Composables/useDiscussionApi', () => ({
  useDiscussionApi: () => ({ fetchThread, fetchMentionables }),
}));

vi.mock('@/Composables/useDiscussionChannel', () => ({
  useDiscussionChannel: () => ({ members: ref([]), connect: vi.fn(), disconnect: vi.fn(), whisperTyping: vi.fn() }),
}));

import RecordActivity from '../RecordActivity.vue';

const mountActivity = () => mount(RecordActivity, {
  props: { commentableType: 'meeting', commentableId: 'm1' },
  global: {
    stubs: {
      ...commonStubs,
      CommentComposer: { template: '<div class="composer-stub" />', methods: { reset: vi.fn() } },
      CommentThread: { props: ['comment'], template: '<div class="thread-stub">{{ comment.body }}</div>' },
    },
  },
});

describe('RecordActivity', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    fetchMentionables.mockResolvedValue([]);
  });

  /** The change history is about the record, so it lives in the record's ⋯ menu, not here. */
  it('shows only comments, without loading the change history', async () => {
    fetchThread.mockResolvedValue([{ id: 'c1', body: 'Pirmas komentaras', replies: [] }]);

    const wrapper = mountActivity();
    await flushPromises();

    expect(wrapper.findAll('.thread-stub').map(thread => thread.text())).toEqual(['Pirmas komentaras']);
    expect(mockFetch).not.toHaveBeenCalled();
  });

  it('invites the first comment when there are none', async () => {
    fetchThread.mockResolvedValue([]);

    const wrapper = mountActivity();
    await flushPromises();

    expect(wrapper.find('.thread-stub').exists()).toBe(false);
    expect(wrapper.text()).toContain('activity.comments_empty');
  });
});
