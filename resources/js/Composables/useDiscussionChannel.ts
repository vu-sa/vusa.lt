/**
 * Live discussion channel — subscribes to the presence channel for a
 * commentable so new comments, edits, deletions, resolves and reactions appear
 * without a reload, and exposes the list of users currently viewing the thread.
 *
 * Mirrors the Echo/Reverb usage in useRealtimeNotifications. The backend gates
 * presence membership on the parent's `view` ability, so the member list is the
 * comment read audience.
 *
 * @see app/Events/CommentBroadcast.php
 * @see routes/channels.php (comments.{type}.{id})
 */

import { onUnmounted, ref } from 'vue';
import type Echo from 'laravel-echo';

import type { CommentData } from '@/Types/discussions';

export interface DiscussionMember {
  id: string;
  name: string;
  profile_photo_path: string | null;
}

export interface DiscussionChannelHandlers {
  onCreated?: (comment: CommentData) => void;
  onUpdated?: (comment: CommentData) => void;
  onDeleted?: (payload: { id: string }) => void;
  onResolved?: (comment: CommentData) => void;
  onReaction?: (comment: CommentData) => void;
  onPoll?: (comment: CommentData) => void;
}

export function useDiscussionChannel(
  commentableType: string,
  commentableId: string,
  handlers: DiscussionChannelHandlers = {},
) {
  const members = ref<DiscussionMember[]>([]);
  const isConnected = ref(false);

  let echo: Echo<'reverb'> | null = null;
  let channel: any = null;
  const channelName = `comments.${commentableType}.${commentableId}`;

  async function connect() {
    if (channel) {
      return;
    }

    try {
      const { initEcho } = await import('@/echo');
      echo = initEcho();

      channel = echo.join(channelName)
        .here((users: DiscussionMember[]) => { members.value = users; })
        .joining((user: DiscussionMember) => { members.value = [...members.value, user]; })
        .leaving((user: DiscussionMember) => {
          members.value = members.value.filter(member => member.id !== user.id);
        })
        .listen('.comment.created', (comment: CommentData) => handlers.onCreated?.(comment))
        .listen('.comment.updated', (comment: CommentData) => handlers.onUpdated?.(comment))
        .listen('.comment.deleted', (payload: { id: string }) => handlers.onDeleted?.(payload))
        .listen('.comment.resolved', (comment: CommentData) => handlers.onResolved?.(comment))
        .listen('.comment.reaction', (comment: CommentData) => handlers.onReaction?.(comment))
        .listen('.comment.poll', (comment: CommentData) => handlers.onPoll?.(comment));

      isConnected.value = true;
    }
    catch (error) {
      console.error('[Reverb] Failed to join discussion channel:', error);
    }
  }

  function disconnect() {
    if (echo && channel) {
      echo.leave(channelName);
      channel = null;
      isConnected.value = false;
      members.value = [];
    }
  }

  /**
   * Broadcast a transient "typing" hint to other members (no persistence).
   */
  function whisperTyping(user: DiscussionMember) {
    channel?.whisper('typing', user);
  }

  onUnmounted(disconnect);

  return { members, isConnected, connect, disconnect, whisperTyping };
}
