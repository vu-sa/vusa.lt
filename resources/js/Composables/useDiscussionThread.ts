import { computed, ref } from 'vue';

import { useDiscussionApi } from '@/Composables/useDiscussionApi';
import { useDiscussionChannel } from '@/Composables/useDiscussionChannel';
import { useToasts } from '@/Composables/useToasts';
import type { CommentData, MentionableUser, PollDraft } from '@/Types/discussions';

export function useDiscussionThread(commentableType: string, commentableId: string) {
  const api = useDiscussionApi(commentableType, commentableId);
  const toasts = useToasts();

  const comments = ref<CommentData[]>([]);
  const mentionables = ref<MentionableUser[]>([]);
  const loading = ref(true);
  const posting = ref(false);
  const mutating = ref(false);

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

  function removeComment(id: string): void {
    const rootIndex = comments.value.findIndex(comment => comment.id === id);
    if (rootIndex !== -1) {
      comments.value.splice(rootIndex, 1);
      return;
    }
    for (const root of comments.value) {
      if (root.replies?.some(reply => reply.id === id)) {
        root.replies = root.replies.filter(reply => reply.id !== id);
        return;
      }
    }
  }

  const { members, connect } = useDiscussionChannel(commentableType, commentableId, {
    onCreated: upsertComment,
    onUpdated: upsertComment,
    onResolved: upsertComment,
    onReaction: upsertComment,
    onPoll: upsertComment,
    onDeleted: ({ id }) => removeComment(id),
  });

  async function post(html: string, parentId?: string): Promise<CommentData | null> {
    const state = parentId ? mutating : posting;
    state.value = true;
    try {
      const comment = await (parentId ? api.postComment(html, parentId) : api.postComment(html));
      upsertComment(comment);
      return comment;
    }
    catch (error) {
      toasts.error((error as Error).message);
      return null;
    }
    finally {
      state.value = false;
    }
  }

  async function createPoll(html: string, poll: PollDraft): Promise<CommentData | null> {
    posting.value = true;
    try {
      const comment = await api.createPoll(html, poll);
      upsertComment(comment);
      return comment;
    }
    catch (error) {
      toasts.error((error as Error).message);
      return null;
    }
    finally {
      posting.value = false;
    }
  }

  async function update(id: string, html: string): Promise<void> {
    mutating.value = true;
    try {
      upsertComment(await api.updateComment(id, html));
    }
    catch (error) {
      toasts.error((error as Error).message);
    }
    finally {
      mutating.value = false;
    }
  }

  async function remove(id: string): Promise<void> {
    try {
      await api.deleteComment(id);
      removeComment(id);
    }
    catch (error) {
      toasts.error((error as Error).message);
    }
  }

  async function mutate(id: string, operation: 'resolve' | 'unresolve'): Promise<void> {
    try {
      upsertComment(operation === 'resolve'
        ? await api.resolveComment(id)
        : await api.unresolveComment(id));
    }
    catch (error) {
      toasts.error((error as Error).message);
    }
  }

  async function toggleReaction(id: string, emoji: string): Promise<void> {
    try {
      upsertComment(await api.toggleReaction(id, emoji));
    }
    catch (error) {
      toasts.error((error as Error).message);
    }
  }

  async function vote(id: string, optionId: string): Promise<void> {
    try {
      upsertComment(await api.togglePollVote(id, optionId));
    }
    catch (error) {
      toasts.error((error as Error).message);
    }
  }

  async function load(): Promise<void> {
    try {
      const [thread, mentions] = await Promise.all([api.fetchThread(), api.fetchMentionables()]);
      comments.value = thread;
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

  return {
    comments,
    mentionables,
    loading,
    posting,
    mutating,
    members,
    rootCount: computed(() => comments.value.length),
    load,
    post,
    createPoll,
    update,
    remove,
    resolve: (id: string) => mutate(id, 'resolve'),
    unresolve: (id: string) => mutate(id, 'unresolve'),
    toggleReaction,
    vote,
  };
}
