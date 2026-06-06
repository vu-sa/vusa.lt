import { mount, flushPromises } from '@vue/test-utils';
import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import type { CommentData } from '@/Types/discussions';

// --- Hoisted mock fns so the module factories can reference them ---
const mocks = vi.hoisted(() => ({
  fetchThread: vi.fn(),
  fetchMentionables: vi.fn(),
  postComment: vi.fn(),
  toggleReaction: vi.fn(),
  resolveComment: vi.fn(),
  unresolveComment: vi.fn(),
  updateComment: vi.fn(),
  deleteComment: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.mock('@/Composables/useDiscussionApi', () => ({
  useDiscussionApi: () => mocks,
}));

vi.mock('@/Composables/useDiscussionChannel', () => ({
  useDiscussionChannel: () => ({ members: ref([]), connect: vi.fn(), disconnect: vi.fn(), whisperTyping: vi.fn() }),
}));

// The Tiptap-based composer is heavy; stub it to a button that emits submit.
vi.mock('@/Components/Discussions/CommentComposer.vue', () => ({
  default: {
    name: 'CommentComposer',
    emits: ['submit', 'cancel'],
    template: '<button class="composer-stub" @click="$emit(\'submit\', \'<p>new</p>\')">composer</button>',
  },
}));

import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';

function makeComment(overrides: Partial<CommentData> = {}): CommentData {
  return {
    id: overrides.id ?? 'c1',
    parent_id: null,
    thread_root_id: null,
    kind: 'comment',
    body: '<p>Root body</p>',
    metadata: null,
    user: { id: 'u1', name: 'Author', profile_photo_path: null },
    created_at: new Date().toISOString(),
    edited_at: null,
    resolved_at: null,
    resolved_by: null,
    is_resolved: false,
    mentioned_user_ids: [],
    reactions: [],
    replies: [],
    can: { update: false, delete: false, resolve: true },
    ...overrides,
  };
}

const stubs = {
  UserAvatar: { template: '<span class="avatar" />' },
  CommentReactions: { template: '<div class="reactions" />' },
  MessagesSquare: { template: '<span />' },
  DropdownMenu: { template: '<div><slot /></div>' },
  DropdownMenuTrigger: { template: '<div><slot /></div>' },
  DropdownMenuContent: { template: '<div><slot /></div>' },
  DropdownMenuItem: { template: '<div><slot /></div>' },
};

describe('DiscussionPanel', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.fetchMentionables.mockResolvedValue([]);
  });

  it('renders fetched threads with their replies', async () => {
    mocks.fetchThread.mockResolvedValue([
      makeComment({ id: 'root1', body: '<p>Root one</p>', replies: [makeComment({ id: 'rep1', parent_id: 'root1', thread_root_id: 'root1', body: '<p>A reply</p>' })] }),
    ]);

    const wrapper = mount(DiscussionPanel, {
      props: { commentableType: 'agendaItem', commentableId: 'ai1' },
      global: { stubs },
    });
    await flushPromises();

    expect(wrapper.html()).toContain('Root one');
    expect(wrapper.html()).toContain('A reply');
  });

  it('shows an empty state when there are no comments', async () => {
    mocks.fetchThread.mockResolvedValue([]);

    const wrapper = mount(DiscussionPanel, {
      props: { commentableType: 'meeting', commentableId: 'm1' },
      global: { stubs },
    });
    await flushPromises();

    expect(wrapper.text()).toContain('Pradėkite diskusiją');
  });

  it('posts a new root comment and appends it', async () => {
    mocks.fetchThread.mockResolvedValue([]);
    mocks.postComment.mockResolvedValue(makeComment({ id: 'new1', body: '<p>Posted</p>' }));

    const wrapper = mount(DiscussionPanel, {
      props: { commentableType: 'meeting', commentableId: 'm1' },
      global: { stubs },
    });
    await flushPromises();

    // The root composer is the first stub.
    await wrapper.find('.composer-stub').trigger('click');
    await flushPromises();

    expect(mocks.postComment).toHaveBeenCalledWith('<p>new</p>');
    expect(wrapper.html()).toContain('Posted');
  });

  it('filters out resolved threads when toggled to open-only', async () => {
    mocks.fetchThread.mockResolvedValue([
      makeComment({ id: 'open1', body: '<p>Open thread</p>' }),
      makeComment({ id: 'done1', body: '<p>Done thread</p>', is_resolved: true, resolved_at: new Date().toISOString() }),
    ]);

    const wrapper = mount(DiscussionPanel, {
      props: { commentableType: 'meeting', commentableId: 'm1' },
      global: { stubs },
    });
    await flushPromises();

    expect(wrapper.html()).toContain('Done thread');

    // Toggle to "open only".
    await wrapper.findAll('button').find(b => b.text().includes('Rodyti tik neišspręstus'))!.trigger('click');
    await flushPromises();

    expect(wrapper.html()).toContain('Open thread');
    expect(wrapper.html()).not.toContain('Done thread');
  });
});
