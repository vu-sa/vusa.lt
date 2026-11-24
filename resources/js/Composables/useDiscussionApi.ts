import { apiRequest } from '@/Composables/apiRequest';
import type { ApiMethod } from '@/Composables/apiRequest';
import type { CommentData, CommentDraft, DiscussionMeta, MentionableUser } from '@/Types/discussions';

export function useDiscussionApi(commentableType: string, commentableId: string) {
  async function request<T>(url: string, method: ApiMethod, body?: unknown): Promise<T> {
    return (await send<T>(url, method, body)).data;
  }

  async function send<T, M = unknown>(url: string, method: ApiMethod, body?: unknown): Promise<{ data: T; meta: M | null }> {
    const result = await apiRequest<T>(url, { method, body });
    if (result.status === 0) throw new TypeError(result.message ?? 'Network error');
    if (!result.ok || result.body === null) {
      throw new Error(result.message ?? 'Request failed');
    }

    return { data: result.data as T, meta: result.meta as M | null };
  }

  const params = { commentableType, commentableId };

  return {
    async fetchThread(): Promise<{ comments: CommentData[]; meta: DiscussionMeta | null }> {
      const { data, meta } = await send<CommentData[], DiscussionMeta>(route('api.v1.admin.comments.index', params), 'GET');
      return { comments: data, meta };
    },

    fetchMentionables(): Promise<MentionableUser[]> {
      return request<MentionableUser[]>(route('api.v1.admin.comments.mentionables', params), 'GET');
    },

    postComment(draft: CommentDraft, parentId?: string | null): Promise<CommentData> {
      return request<CommentData>(route('api.v1.admin.comments.store', params), 'POST', {
        body: draft.body,
        parent_id: parentId ?? null,
        kind: draft.mode,
        title: draft.mode === 'topic' ? draft.title : undefined,
        metadata: draft.mode === 'poll' ? { poll: draft.poll } : undefined,
        image_ids: draft.imageIds.length ? draft.imageIds : undefined,
        dutiable_id: draft.dutiableId ?? undefined,
      });
    },

    togglePollVote(id: string, optionId: string): Promise<CommentData> {
      return request<CommentData>(route('api.v1.admin.comments.poll.votes.toggle', { comment: id }), 'PUT', {
        option_id: optionId,
      });
    },

    updateComment(id: string, body: string): Promise<CommentData> {
      return request<CommentData>(route('api.v1.admin.comments.update', { comment: id }), 'PATCH', { body });
    },

    deleteComment(id: string): Promise<CommentData> {
      return request<CommentData>(route('api.v1.admin.comments.destroy', { comment: id }), 'DELETE');
    },

    closeTopic(id: string): Promise<CommentData> {
      return request<CommentData>(route('api.v1.admin.comments.resolve', { comment: id }), 'POST');
    },

    reopenTopic(id: string): Promise<CommentData> {
      return request<CommentData>(route('api.v1.admin.comments.resolve', { comment: id }), 'DELETE');
    },

    toggleReaction(id: string, emoji: string): Promise<CommentData> {
      return request<CommentData>(route('api.v1.admin.comments.reactions.toggle', { comment: id }), 'PUT', { emoji });
    },
  };
}
