import { mount, flushPromises } from '@vue/test-utils';
import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { commonStubs } from '@/tests/stubs';
import type { CommentData, DiscussionLayout } from '@/Types/discussions';

// --- Hoisted mock fns so the module factories can reference them ---
const mocks = vi.hoisted(() => ({
  fetchThread: vi.fn(),
  fetchMentionables: vi.fn(),
  postComment: vi.fn(),
  toggleReaction: vi.fn(),
  closeTopic: vi.fn(),
  reopenTopic: vi.fn(),
  updateComment: vi.fn(),
  deleteComment: vi.fn(),
  resetComposer: vi.fn(),
  nextDraft: { body: '<p>new</p>', mode: 'comment', imageIds: [], dutiableId: null } as Record<string, unknown>,
  channel: {} as Record<string, (comment: CommentData) => void>,
}));

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.mock('@/Composables/useDiscussionApi', () => ({
  useDiscussionApi: () => mocks,
}));

vi.mock('@/Composables/useDiscussionChannel', () => ({
  useDiscussionChannel: (_type: string, _id: string, handlers: Record<string, (comment: CommentData) => void>) => {
    mocks.channel = handlers;
    return { members: ref([]), connect: vi.fn(), disconnect: vi.fn(), whisperTyping: vi.fn() };
  },
}));

// The Tiptap-based composer is heavy; stub it to a button that emits submit.
vi.mock('@/Components/Discussions/CommentComposer.vue', async () => {
  const { defineComponent } = await import('vue');

  return {
    __esModule: true,
    default: defineComponent({
      name: 'CommentComposer',
      emits: ['submit', 'cancel'],
      methods: { reset: mocks.resetComposer },
      props: ['modes'],
      template: '<button class="composer-stub" :data-modes="(modes || []).join(\',\')" @click="$emit(\'submit\', draft)">composer</button>',
      data: () => ({ draft: mocks.nextDraft }),
    }),
  };
});

import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';

function makeComment(overrides: Partial<CommentData> = {}): CommentData {
  return {
    id: overrides.id ?? 'c1',
    parent_id: null,
    thread_root_id: null,
    kind: 'comment',
    body: '<p>Root body</p>',
    is_erased: false,
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
    can: { update: false, delete: false, resolve: false },
    ...overrides,
  };
}

const stubs = {
  ...commonStubs,
  UserAvatar: { template: '<span class="avatar" />' },
  CommentReactions: { template: '<div class="reactions" />' },
  MessagesSquare: { template: '<span />' },
  DropdownMenu: { template: '<div><slot /></div>' },
  DropdownMenuTrigger: { template: '<div><slot /></div>' },
  DropdownMenuContent: { template: '<div><slot /></div>' },
  DropdownMenuItem: { template: '<div><slot /></div>' },
};

function thread(comments: CommentData[], layout: DiscussionLayout = 'comments', canModerate = false) {
  return {
    comments,
    meta: {
      capabilities: { layout, topics: layout !== 'conversation', polls: layout !== 'conversation', images: true },
      me: { duties: [], default_dutiable_id: null, can_moderate: canModerate },
    },
  };
}

const mountPanel = (commentableType = 'meeting', extraProps: Record<string, unknown> = {}) => mount(DiscussionPanel, {
  props: { commentableType, commentableId: 'x1', ...extraProps },
  global: { stubs },
});

const topic = (overrides: Partial<CommentData> = {}) => makeComment({ kind: 'topic', title: 'Kaip ruoštis posėdžiui?', ...overrides });

describe('DiscussionPanel', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.fetchMentionables.mockResolvedValue([]);
    mocks.nextDraft = { body: '<p>new</p>', mode: 'comment', imageIds: [], dutiableId: null };
  });

  it('refetches the mention list when the parent\'s people change', async () => {
    mocks.fetchThread.mockResolvedValue(thread([]));

    const wrapper = mountPanel('supportRequest', { mentionablesKey: 'u1' });
    await flushPromises();
    expect(mocks.fetchMentionables).toHaveBeenCalledTimes(1);

    await wrapper.setProps({ mentionablesKey: 'u1,u2' });
    await flushPromises();

    expect(mocks.fetchMentionables).toHaveBeenCalledTimes(2);
    expect(mocks.fetchThread).toHaveBeenCalledTimes(1);
  });

  it('renders fetched threads with their replies', async () => {
    mocks.fetchThread.mockResolvedValue(thread([
      makeComment({ id: 'root1', body: '<p>Root one</p>', replies: [makeComment({ id: 'rep1', parent_id: 'root1', thread_root_id: 'root1', body: '<p>A reply</p>' })] }),
    ]));

    const wrapper = mountPanel('agendaItem');
    await flushPromises();

    expect(wrapper.html()).toContain('Root one');
    expect(wrapper.html()).toContain('A reply');
  });

  it('shows the latest replies and folds the earlier ones', async () => {
    const replies = [1, 2, 3, 4].map(n => makeComment({ id: `r${n}`, parent_id: 'root1', thread_root_id: 'root1', body: `<p>Reply ${n}</p>` }));
    mocks.fetchThread.mockResolvedValue(thread([makeComment({ id: 'root1', replies })]));

    const wrapper = mountPanel();
    await flushPromises();

    expect(wrapper.html()).not.toContain('Reply 1');
    expect(wrapper.html()).toContain('Reply 4');
    await wrapper.find('[data-testid="show-earlier-replies"]').trigger('click');
    expect(wrapper.html()).toContain('Reply 1');
  });

  it('posts a new root comment as a draft and appends it', async () => {
    mocks.fetchThread.mockResolvedValue(thread([]));
    mocks.postComment.mockResolvedValue(makeComment({ id: 'new1', body: '<p>Posted</p>' }));

    const wrapper = mountPanel();
    await flushPromises();

    await wrapper.find('.composer-stub').trigger('click');
    await flushPromises();

    expect(mocks.postComment).toHaveBeenCalledWith(mocks.nextDraft, undefined);
    expect(wrapper.html()).toContain('Posted');
    expect(mocks.resetComposer).toHaveBeenCalledOnce();
  });

  it('starts every composer on a comment and offers topics and polls only where the record allows them', async () => {
    mocks.fetchThread.mockResolvedValue(thread([], 'topics'));
    const learning = mountPanel('learningStep');
    await flushPromises();
    expect(learning.find('.composer-stub').attributes('data-modes')).toBe('comment,topic,poll');
    expect(learning.find('[data-testid="discussion-empty"]').text()).toBe('discussions.empty_topics');

    mocks.fetchThread.mockResolvedValue(thread([], 'conversation'));
    const support = mountPanel('supportRequest');
    await flushPromises();
    expect(support.find('.composer-stub').attributes('data-modes')).toBe('comment');
  });

  it('lists open topics above comments and keeps closed topics folded away', async () => {
    mocks.fetchThread.mockResolvedValue(thread([
      makeComment({ id: 'c1', body: '<p>Plain comment</p>' }),
      topic({ id: 't1', title: 'Atvira tema' }),
      topic({ id: 't2', title: 'Užbaigta tema', is_closed: true, is_resolved: true }),
    ]));

    const wrapper = mountPanel();
    await flushPromises();

    const topics = wrapper.find('[data-slot="discussion-topics"]');
    expect(topics.text()).toContain('Atvira tema');
    expect(topics.text()).not.toContain('Užbaigta tema');
    expect(wrapper.find('[data-slot="discussion-comments"]').text()).toContain('Plain comment');

    await wrapper.find('[data-testid="toggle-closed-topics"]').trigger('click');
    expect(topics.text()).toContain('Užbaigta tema');
  });

  it('opens a topic the author just started, and closes it from its body', async () => {
    mocks.fetchThread.mockResolvedValue(thread([], 'topics'));
    mocks.nextDraft = { body: '<p>Body</p>', mode: 'topic', title: 'Nauja tema', imageIds: [], dutiableId: null };
    mocks.postComment.mockResolvedValue(topic({ id: 't9', title: 'Nauja tema', body: '<p>Body</p>', can: { update: true, delete: true, resolve: true } }));
    mocks.closeTopic.mockResolvedValue(topic({ id: 't9', title: 'Nauja tema', is_closed: true, is_resolved: true, can: { update: true, delete: true, resolve: true } }));

    const wrapper = mountPanel('learningStep');
    await flushPromises();
    await wrapper.find('.composer-stub').trigger('click');
    await flushPromises();

    expect(wrapper.find('[data-slot="topic-body"]').text()).toContain('Body');
    await wrapper.find('[data-testid="topic-close"]').trigger('click');
    await flushPromises();

    expect(mocks.closeTopic).toHaveBeenCalledWith('t9');
    expect(wrapper.find('[data-testid="toggle-closed-topics"]').exists()).toBe(true);
  });

  it('leaves a "Komentaras ištrintas" placeholder where a deleted comment was, replies kept', async () => {
    const reply = makeComment({ id: 'rep1', parent_id: 'root1', thread_root_id: 'root1', body: '<p>Rugsėjo 30 d.</p>' });
    mocks.fetchThread.mockResolvedValue(thread([
      makeComment({ id: 'root1', body: '<p>Kada kitas posėdis?</p>', can: { update: true, delete: true, resolve: false }, replies: [reply] }),
    ]));
    mocks.deleteComment.mockResolvedValue(makeComment({
      id: 'root1', body: '', is_erased: true, user: { id: null, name: null, profile_photo_path: null },
      can: { update: false, delete: false, resolve: false },
      // The destroy endpoint returns the comment alone; its replies stay where they are.
      replies: undefined,
    }));

    const wrapper = mountPanel();
    await flushPromises();

    await wrapper.find('[data-testid="comment-delete"]').trigger('click');
    await flushPromises();

    expect(mocks.deleteComment).toHaveBeenCalledWith('root1');
    expect(wrapper.find('[data-testid="comment-erased"]').text()).toContain('Komentaras ištrintas');
    expect(wrapper.html()).not.toContain('Kada kitas posėdis?');
    expect(wrapper.html()).toContain('Rugsėjo 30 d.');
  });

  it('shows the duty the author wrote as', async () => {
    mocks.fetchThread.mockResolvedValue(thread([
      makeComment({ author_duty: { dutiable_id: 'd1', name: 'Koordinatorius', institution: 'MIF SA', use_original_duty_name: true } }),
    ]));

    const wrapper = mountPanel();
    await flushPromises();

    expect(wrapper.find('[data-testid="comment-author-duty"]').text()).toBe('Koordinatorius · MIF SA');
  });

  it('marks the reply button while its reply box is open and closes the box on a second press', async () => {
    mocks.fetchThread.mockResolvedValue(thread([makeComment({ id: 'c1' })]));
    const wrapper = mountPanel();
    await flushPromises();
    const reply = () => wrapper.find('[data-testid="comment-reply"]');

    await reply().trigger('click');
    await flushPromises();
    expect(reply().attributes('aria-expanded')).toBe('true');
    expect(wrapper.findAll('.composer-stub')).toHaveLength(2);

    await reply().trigger('click');
    await flushPromises();
    expect(reply().attributes('aria-expanded')).toBe('false');
    expect(wrapper.findAll('.composer-stub')).toHaveLength(1);
  });

  it('keeps moderation actions on a topic someone else posts while the page is open', async () => {
    mocks.fetchThread.mockResolvedValue(thread([], 'comments', true));
    const wrapper = mountPanel();
    await flushPromises();

    mocks.channel.onCreated!(topic({ id: 't-live', title: 'Live topic', user: { id: 'someone-else', name: 'Ona', profile_photo_path: null }, can: { update: true, delete: true, resolve: true } }));
    await flushPromises();
    await wrapper.find('[data-testid="topic-toggle"]').trigger('click');

    expect(wrapper.find('[data-testid="topic-close"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="comment-delete"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="comment-edit"]').exists()).toBe(false);
  });

  it('closes an open reply box when its topic is closed', async () => {
    mocks.fetchThread.mockResolvedValue(thread([topic({ id: 't1', can: { update: false, delete: false, resolve: true } })]));
    mocks.closeTopic.mockResolvedValue(topic({ id: 't1', is_closed: true, is_resolved: true, can: { update: false, delete: false, resolve: true } }));
    const wrapper = mountPanel();
    await flushPromises();
    await wrapper.find('[data-testid="topic-toggle"]').trigger('click');
    await wrapper.find('[data-testid="topic-reply"]').trigger('click');
    await flushPromises();
    expect(wrapper.findAll('.composer-stub')).toHaveLength(2);

    await wrapper.find('[data-testid="topic-close"]').trigger('click');
    await flushPromises();

    expect(wrapper.findAll('.composer-stub')).toHaveLength(1);
  });

  it('likes a comment from its action bar', async () => {
    mocks.fetchThread.mockResolvedValue(thread([makeComment({ id: 'c1' })]));
    mocks.toggleReaction.mockResolvedValue(makeComment({ id: 'c1', reactions: [{ emoji: '👍', count: 1, reacted_by_me: true, users: [{ id: 'u1', name: 'Author' }] }] }));

    const wrapper = mountPanel();
    await flushPromises();
    await wrapper.find('[data-testid="comment-like"]').trigger('click');
    await flushPromises();

    expect(mocks.toggleReaction).toHaveBeenCalledWith('c1', '👍');
    expect(wrapper.find('[data-testid="comment-like"]').attributes('aria-pressed')).toBe('true');
  });

  it('dates a duty that had already ended when the comment was written', async () => {
    mocks.fetchThread.mockResolvedValue(thread([
      makeComment({ id: 'c1', created_at: '2026-10-01T10:00:00Z', author_duty: { dutiable_id: 'd1', name: 'Koordinatorius', institution: 'MIF SA', use_original_duty_name: true, start_date: '2024-09-01', end_date: '2025-06-30', is_current: false } }),
      makeComment({ id: 'c2', created_at: '2025-03-01T10:00:00Z', author_duty: { dutiable_id: 'd1', name: 'Koordinatorius', institution: 'MIF SA', use_original_duty_name: true, start_date: '2024-09-01', end_date: '2025-06-30', is_current: false } }),
    ]));

    const wrapper = mountPanel();
    await flushPromises();

    const lines = wrapper.findAll('[data-testid="comment-author-duty"]').map(line => line.text());
    expect(lines[0]).toBe('Koordinatorius · MIF SA · discussions.author_duty.period');
    expect(lines[1]).toBe('Koordinatorius · MIF SA');
  });

  it('renders stored nested lists with the comment content styling hook', async () => {
    mocks.fetchThread.mockResolvedValue(thread([makeComment({ body: '<ul><li><p>First</p><ol start="3"><li><p>Nested</p></li></ol></li></ul>' })]));
    const wrapper = mountPanel();
    await flushPromises();

    expect(wrapper.find('.comment-content > ul > li > p').text()).toBe('First');
    expect(wrapper.find('.comment-content ol').attributes('start')).toBe('3');
    expect(wrapper.find('.comment-content ol li p').text()).toBe('Nested');
  });

  it('posts replies with the correct parent on the support request surface', async () => {
    mocks.fetchThread.mockResolvedValue(thread([makeComment({ id: 'root1' })], 'conversation'));
    mocks.postComment.mockResolvedValue(makeComment({ id: 'reply1', body: '<p>new</p>', parent_id: 'root1', thread_root_id: 'root1' }));
    const wrapper = mountPanel('supportRequest');
    await flushPromises();
    await wrapper.find('[data-testid="comment-reply"]').trigger('click');
    await flushPromises();
    await wrapper.findAll('.composer-stub')[1]!.trigger('click');
    await flushPromises();

    expect(mocks.postComment).toHaveBeenCalledWith(mocks.nextDraft, 'root1');
    wrapper.unmount();
  });

  it('loads composers asynchronously and mounts the reply editor only after opening a reply', async () => {
    mocks.fetchThread.mockResolvedValue(thread([makeComment()]));
    const wrapper = mountPanel();
    await flushPromises();
    expect(wrapper.findAll('.composer-stub')).toHaveLength(1);

    await wrapper.find('[data-testid="comment-reply"]').trigger('click');
    await flushPromises();

    const composers = wrapper.findAllComponents({ name: 'CommentComposer' });
    expect(composers).toHaveLength(2);
    for (const composer of composers) {
      const parentType = composer.vm.$.parent?.type as { __asyncLoader?: unknown };
      expect(parentType.__asyncLoader).toBeTypeOf('function');
    }
    expect(composers[1]!.attributes()).toHaveProperty('autofocus');
    wrapper.unmount();
  });
});
