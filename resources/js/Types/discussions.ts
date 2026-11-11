/**
 * Types for the discussion (comments) framework. Mirrors
 * app/Http/Resources/CommentResource.php.
 */

import type { ImageData } from '@/Types/media';

export interface CommentUser {
  /** Null once the comment is erased: its author is not kept. */
  id: string | null;
  name: string | null;
  profile_photo_path: string | null;
  pronouns?: Record<string, string> | null;
}

/** The duty its author spoke as, saved with the comment so old comments keep the role held then. */
export interface CommentAuthorDuty {
  dutiable_id: string;
  name: string | null;
  /** The full institution name, for the duty picker. */
  institution: string | null;
  /** The padalinys short name shown beside a comment's author. */
  tenant?: string | null;
  use_original_duty_name: boolean;
  start_date?: string | null;
  end_date?: string | null;
  is_current?: boolean;
}

export type DiscussionLayout = 'topics' | 'conversation' | 'comments';

export interface DiscussionCapabilities {
  layout: DiscussionLayout;
  topics: boolean;
  polls: boolean;
  images: boolean;
}

export interface DiscussionMeta {
  capabilities: DiscussionCapabilities;
  me: {
    duties: CommentAuthorDuty[];
    default_dutiable_id: string | null;
    /** The viewer may delete others' comments and close topics here. */
    can_moderate?: boolean;
  };
}

export type ComposerMode = 'comment' | 'topic' | 'poll';

/** What the composer hands to the panel on submit. */
export interface CommentDraft {
  body: string;
  mode: ComposerMode;
  title?: string;
  poll?: PollDraft;
  imageIds: number[];
  dutiableId: string | null;
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
  kind: 'comment' | 'poll' | 'topic' | string;
  /** Topics only. */
  title?: string | null;
  body: string;
  /** Deleted: text and author are gone, only the placeholder keeps its place in the thread. */
  is_erased: boolean;
  metadata: Record<string, unknown> | null;
  poll?: PollData | null;
  user: CommentUser;
  created_at: string | null;
  edited_at: string | null;
  resolved_at: string | null;
  resolved_by: string | null;
  is_resolved: boolean;
  /** A topic its author or a moderator closed. */
  is_closed?: boolean;
  author_duty?: CommentAuthorDuty | null;
  images?: ImageData[];
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
