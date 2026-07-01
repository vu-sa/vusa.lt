/**
 * Imperative client for the discussion API. The endpoints serve dynamic,
 * per-comment operations (post / edit / delete / resolve / react) that don't
 * fit useApiMutation's setup-time URL binding, so this composable wraps fetch
 * with the project's standard JSON + CSRF headers and the `{ success, data }`
 * envelope.
 */

import { usePage } from '@inertiajs/vue3';

import type { CommentData, MentionableUser, PollDraft } from '@/Types/discussions';

export function useDiscussionApi(commentableType: string, commentableId: string) {
  const page = usePage();

  async function request<T>(url: string, method: string, body?: unknown): Promise<T> {
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

    return json.data as T;
  }

  const params = { commentableType, commentableId };

  return {
    fetchThread(resolved?: boolean): Promise<CommentData[]> {
      const url = route('api.v1.admin.comments.index', params);
      const query = resolved === undefined ? '' : `?resolved=${resolved ? 1 : 0}`;
      return request<CommentData[]>(url + query, 'GET');
    },

    fetchMentionables(): Promise<MentionableUser[]> {
      return request<MentionableUser[]>(route('api.v1.admin.comments.mentionables', params), 'GET');
    },

    postComment(body: string, parentId?: string | null): Promise<CommentData> {
      return request<CommentData>(route('api.v1.admin.comments.store', params), 'POST', {
        body,
        parent_id: parentId ?? null,
      });
    },

    createPoll(body: string, poll: PollDraft): Promise<CommentData> {
      return request<CommentData>(route('api.v1.admin.comments.store', params), 'POST', {
        body,
        kind: 'poll',
        metadata: { poll },
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

    deleteComment(id: string): Promise<{ id: string }> {
      return request<{ id: string }>(route('api.v1.admin.comments.destroy', { comment: id }), 'DELETE');
    },

    resolveComment(id: string): Promise<CommentData> {
      return request<CommentData>(route('api.v1.admin.comments.resolve', { comment: id }), 'POST');
    },

    unresolveComment(id: string): Promise<CommentData> {
      return request<CommentData>(route('api.v1.admin.comments.resolve', { comment: id }), 'DELETE');
    },

    toggleReaction(id: string, emoji: string): Promise<CommentData> {
      return request<CommentData>(route('api.v1.admin.comments.reactions.toggle', { comment: id }), 'PUT', { emoji });
    },
  };
}
