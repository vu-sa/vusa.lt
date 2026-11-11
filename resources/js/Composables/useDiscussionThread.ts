import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { useDiscussionApi } from '@/Composables/useDiscussionApi';
import { useDiscussionChannel } from '@/Composables/useDiscussionChannel';
import { useToasts } from '@/Composables/useToasts';
import type { CommentData, CommentDraft, DiscussionMeta, MentionableUser } from '@/Types/discussions';

const FALLBACK_META: DiscussionMeta = {
  capabilities: { layout: 'comments', topics: false, polls: true, images: false },
  me: { duties: [], default_dutiable_id: null },
};

export function useDiscussionThread(commentableType: string, commentableId: string) {
  const api = useDiscussionApi(commentableType, commentableId);
  const toasts = useToasts();
  const currentUserId = computed(() => (usePage().props.auth as { user?: { id: string } } | undefined)?.user?.id ?? null);

  const comments = ref<CommentData[]>([]);
  const mentionables = ref<MentionableUser[]>([]);
  const meta = ref<DiscussionMeta>(FALLBACK_META);
  const loading = ref(true);
  const posting = ref(false);
  const mutating = ref(false);

  function findComment(id: string): CommentData | undefined {
    for (const root of comments.value) {
      if (root.id === id) return root;
      const reply = root.replies?.find(candidate => candidate.id === id);
      if (reply) return reply;
    }
    return undefined;
  }

  function upsertComment(incoming: CommentData): void {
    if (!incoming.parent_id) {
      const index = comments.value.findIndex(comment => comment.id === incoming.id);
      if (index === -1) {
        comments.value.push(incoming);
      }
      else {
        comments.value[index] = { ...incoming, replies: incoming.replies ?? comments.value[index]!.replies };
      }
      return;
    }

    const root = comments.value.find(comment => comment.id === (incoming.thread_root_id ?? incoming.parent_id));
    if (!root) {
      return;
    }
    root.replies = root.replies ?? [];
    const replyIndex = root.replies.findIndex(reply => reply.id === incoming.id);
    if (replyIndex === -1) {
      root.replies.push(incoming);
    }
    else {
      root.replies[replyIndex] = incoming;
    }
  }

  /**
   * A broadcast carries the sender's permission flags, so a viewer keeps the flags they already
   * had and gets none on someone else's new comment until the next load.
   */
  function upsertBroadcast(incoming: CommentData): void {
    const known = findComment(incoming.id);
    const isMine = incoming.user.id !== null && incoming.user.id === currentUserId.value;
    const moderates = meta.value.me.can_moderate === true;
    let can = known?.can;
    if (!can) {
      can = isMine ? incoming.can : { update: false, delete: moderates, resolve: moderates && incoming.kind === 'topic' };
    }
    upsertComment({ ...incoming, can });
  }

  const { members, connect } = useDiscussionChannel(commentableType, commentableId, {
    onCreated: upsertBroadcast,
    onUpdated: upsertBroadcast,
    onResolved: upsertBroadcast,
    onReaction: upsertBroadcast,
    onPoll: upsertBroadcast,
  });

  async function run<T>(operation: () => Promise<T>, state = mutating): Promise<T | null> {
    state.value = true;
    try {
      return await operation();
    }
    catch (error) {
      toasts.error((error as Error).message);
      return null;
    }
    finally {
      state.value = false;
    }
  }

  async function post(draft: CommentDraft, parentId?: string): Promise<CommentData | null> {
    const comment = await run(() => api.postComment(draft, parentId), parentId ? mutating : posting);
    if (comment) upsertComment(comment);
    return comment;
  }

  async function apply(operation: () => Promise<CommentData>): Promise<void> {
    const comment = await run(operation);
    if (comment) upsertComment(comment);
  }

  async function load(): Promise<void> {
    try {
      const [thread, mentions] = await Promise.all([api.fetchThread(), api.fetchMentionables()]);
      comments.value = thread.comments;
      meta.value = thread.meta ?? FALLBACK_META;
      mentionables.value = mentions;
    }
    catch (error) {
      toasts.error((error as Error).message);
    }
    finally {
      loading.value = false;
    }

    connect();
  }

  async function reloadMentionables(): Promise<void> {
    try {
      mentionables.value = await api.fetchMentionables();
    }
    catch (error) {
      toasts.error((error as Error).message);
    }
  }

  return {
    comments,
    mentionables,
    meta,
    loading,
    posting,
    mutating,
    members,
    load,
    reloadMentionables,
    post,
    update: (id: string, html: string) => apply(() => api.updateComment(id, html)),
    // The comment stays as a "Komentaras ištrintas" placeholder so its replies keep their place.
    remove: (id: string) => apply(() => api.deleteComment(id)),
    close: (id: string) => apply(() => api.closeTopic(id)),
    reopen: (id: string) => apply(() => api.reopenTopic(id)),
    toggleReaction: (id: string, emoji: string) => apply(() => api.toggleReaction(id, emoji)),
    vote: (id: string, optionId: string) => apply(() => api.togglePollVote(id, optionId)),
  };
}

export type DiscussionThread = ReturnType<typeof useDiscussionThread>;
