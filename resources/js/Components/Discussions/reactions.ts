import type { Component } from 'vue';
import { CircleCheck, Eye, HandHeart, Heart, PartyPopper, ThumbsUp } from '@lucide/vue';

export interface ReactionDefinition {
  /** The stored value; mirrors Comment::ALLOWED_REACTIONS. */
  emoji: string;
  icon: Component;
  label: string;
  /** Solid icons read as "chosen"; outline ones stay outlined and take the brand colour. */
  fillWhenActive: boolean;
}

/** Drawn as admin icons rather than emoji glyphs, which render differently on every platform. */
export const REACTIONS: ReactionDefinition[] = [
  { emoji: '👍', icon: ThumbsUp, label: 'discussions.reactions.like', fillWhenActive: true },
  { emoji: '❤️', icon: Heart, label: 'discussions.reactions.love', fillWhenActive: true },
  { emoji: '✅', icon: CircleCheck, label: 'discussions.reactions.agree', fillWhenActive: false },
  { emoji: '🎉', icon: PartyPopper, label: 'discussions.reactions.celebrate', fillWhenActive: false },
  { emoji: '👀', icon: Eye, label: 'discussions.reactions.looking', fillWhenActive: false },
  { emoji: '🙏', icon: HandHeart, label: 'discussions.reactions.thanks', fillWhenActive: false },
];

export function reactionFor(emoji: string): ReactionDefinition | undefined {
  return REACTIONS.find(reaction => reaction.emoji === emoji);
}
