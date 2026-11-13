/**
 * Imperative client for the discussion API. The endpoints serve dynamic,
 * per-comment operations (post / edit / delete / resolve / react) that don't
 * fit useApiMutation's setup-time URL binding, so this composable wraps fetch
 * with the project's standard JSON + CSRF headers and the `{ success, data }`
 * envelope.
 */

import { usePage } from '@inertiajs/vue3';

import type { CommentData, CommentDraft, DiscussionMeta, MentionableUser } from '@/Types/discussions';

export function useDiscussionApi(commentableType: string, commentableId: string) {
  const page = usePage();

  async function request<T>(url: string, method: string, body?: unknown): Promise<T> {
    return (await send<T>(url, method, body)).data;
  }

  async function send<T, M = unknown>(url: string, method: string, body?: unknown): Promise<{ data: T; meta: M | null }> {
    const csrf = (page.props.csrf_token as string | undefined) ?? '';

    const response = await fetch(url, {
      method,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf,
      },
      credentials: 'same-origin',
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });

    const json = await response.json().catch(() => null);

    if (!response.ok || !json?.success) {
      throw new Error(json?.message ?? 'Request failed');
    }

    return { data: json.data as T, meta: (json.meta ?? null) as M | null };
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
