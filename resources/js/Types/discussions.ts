/**
 * Types for the discussion (comments) framework. Mirrors
 * app/Http/Resources/CommentResource.php.
 */

export interface CommentUser {
  id: string;
  name: string | null;
  profile_photo_path: string | null;
}

export interface CommentReactionTally {
  emoji: string;
  count: number;
  reacted_by_me: boolean;
  users: { id: string; name: string | null }[];
}

export interface CommentPermissions {
  update: boolean;
  delete: boolean;
  resolve: boolean;
}

export interface PollOption {
  id: string;
  label: string;
}

export interface PollVoter {
  id: string;
  name: string | null;
}

export interface PollTally {
  option_id: string;
  count: number;
  voters: PollVoter[];
}

export interface PollData {
  options: PollOption[];
  allow_multiple: boolean;
  closes_at: string | null;
  is_closed: boolean;
  total_votes: number;
  tallies: PollTally[];
  my_option_ids: string[];
}

/**
 * Payload sent when creating a poll. Clients supply option labels only — the
 * server assigns each option a stable id.
 */
export interface PollDraft {
  options: { label: string }[];
  allow_multiple: boolean;
  closes_at: string | null;
}

export interface CommentData {
  id: string;
  parent_id: string | null;
  thread_root_id: string | null;
  kind: string;
  body: string;
  metadata: Record<string, unknown> | null;
  poll?: PollData | null;
  user: CommentUser;
  created_at: string | null;
  edited_at: string | null;
  resolved_at: string | null;
  resolved_by: string | null;
  is_resolved: boolean;
  mentioned_user_ids: string[];
  reactions: CommentReactionTally[];
  replies?: CommentData[];
  can: CommentPermissions;
}

export interface MentionableUser {
  id: string;
  name: string;
  profile_photo_path: string | null;
}

export interface DiscussionFeedItem {
  id: string;
  body: string;
  created_at: string | null;
  commentable_type: string | null;
  commentable_id: string;
  commentable_name: string | null;
}
