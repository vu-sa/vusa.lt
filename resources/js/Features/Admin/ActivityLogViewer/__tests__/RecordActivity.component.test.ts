import { mount, flushPromises } from '@vue/test-utils';
import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import RecordActivity from '../RecordActivity.vue';

import { commonStubs } from '@/tests/stubs';

const fetchThread = vi.fn();
const fetchMentionables = vi.fn();
const postComment = vi.fn();
const mockFetch = vi.fn();
vi.stubGlobal('fetch', mockFetch);

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.mock('@/Composables/useDiscussionApi', () => ({
  useDiscussionApi: () => ({ fetchThread, fetchMentionables, postComment }),
}));

vi.mock('@/Composables/useDiscussionChannel', () => ({
  useDiscussionChannel: () => ({ members: ref([]), connect: vi.fn(), disconnect: vi.fn(), whisperTyping: vi.fn() }),
}));

const mountActivity = (commentableType = 'meeting') => mount(RecordActivity, {
  props: { commentableType, commentableId: 'record1' },
  global: {
    stubs: {
      ...commonStubs,
      CommentComposer: { template: '<div class="composer-stub" />', methods: { reset: vi.fn() } },
      CommentThread: {
        props: ['comment'],
        emits: ['reply'],
        template: `<div class="thread-stub">{{ comment.body }}<button type="button" data-testid="reply" @click="$emit('reply', comment.id, '<p>A reply</p>')" /></div>`,
      },
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

  it('refetches the mention list when the record\'s people change', async () => {
    fetchThread.mockResolvedValue([]);

    const wrapper = mountActivity();
    await flushPromises();
    await wrapper.setProps({ mentionablesKey: 'u1,u2' });
    await flushPromises();

    expect(fetchMentionables).toHaveBeenCalledTimes(2);
    expect(fetchThread).toHaveBeenCalledTimes(1);
  });

  it('offers only the composer when there are no comments yet', async () => {
    fetchThread.mockResolvedValue([]);

    const wrapper = mountActivity();
    await flushPromises();

    expect(wrapper.find('.composer-stub').exists()).toBe(true);
    expect(wrapper.find('.thread-stub').exists()).toBe(false);
    expect(wrapper.find('section > p').exists()).toBe(false);
  });
  it.each(['meeting', 'agendaItem', 'institution', 'duty', 'form', 'problem', 'reservation'])('posts replies with the correct body and parent for %s', async (type) => {
    fetchThread.mockResolvedValue([{ id: 'root1', body: 'Root', replies: [] }]);
    postComment.mockResolvedValue({ id: 'reply1', body: '<p>A reply</p>', parent_id: 'root1', thread_root_id: 'root1' });
    const wrapper = mountActivity(type);
    await flushPromises();

    await wrapper.find('[data-testid="reply"]').trigger('click');
    await flushPromises();

    expect(postComment).toHaveBeenCalledWith('<p>A reply</p>', 'root1');
    wrapper.unmount();
  });
});
