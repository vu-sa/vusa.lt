<template>
  <TransitionGroup
    tag="div"
    class="flex flex-wrap items-center gap-1"
    enter-active-class="transition-[transform,opacity] duration-150 ease-out motion-reduce:transition-none"
    enter-from-class="scale-75 opacity-0"
    leave-active-class="transition-[transform,opacity] duration-100 ease-in motion-reduce:transition-none"
    leave-to-class="scale-75 opacity-0"
    data-slot="comment-reactions"
  >
    <!-- TransitionGroup animates real elements; HoverCard itself renders none. -->
    <span v-for="tally in known" :key="tally.emoji" class="inline-flex">
      <HoverCard :open-delay="250" :close-delay="100">
        <HoverCardTrigger as-child>
          <button
            type="button"
            :aria-pressed="tally.reacted_by_me"
            :aria-label="`${$t(tally.reaction.label)}: ${tally.count}`"
            data-testid="reaction-chip"
            :class="[
              'inline-flex h-7 items-center gap-1.5 border px-2 text-xs font-semibold tabular-nums transition-[transform,color,background-color,border-color] duration-150',
              'motion-safe:active:scale-90 pointer-coarse:min-h-11',
              tally.reacted_by_me
                ? 'border-brand/40 bg-brand/10 text-brand'
                : 'border-border text-muted-foreground hover:border-foreground/30 hover:text-foreground',
            ]"
            @click="$emit('toggle', tally.emoji)"
          >
            <component :is="tally.reaction.icon" :class="['size-3.5', tally.reaction.fillWhenActive && tally.reacted_by_me ? 'fill-current' : '']" aria-hidden="true" />
            <span class="relative inline-flex overflow-hidden">
              <Transition
                mode="out-in"
                enter-active-class="transition-[transform,opacity] duration-150 ease-out motion-reduce:transition-none"
                enter-from-class="translate-y-2 opacity-0"
                leave-active-class="transition-[transform,opacity] duration-100 ease-in motion-reduce:transition-none"
                leave-to-class="-translate-y-2 opacity-0"
              >
                <span :key="tally.count">{{ tally.count }}</span>
              </Transition>
            </span>
          </button>
        </HoverCardTrigger>
        <HoverCardContent side="top" align="start" :side-offset="6" class="w-auto max-w-64 p-2.5 shadow-none" data-slot="reaction-voters">
          <p class="flex items-center gap-1.5 text-xs font-semibold text-foreground">
            <component :is="tally.reaction.icon" class="size-3.5 text-brand" aria-hidden="true" />
            {{ $t(tally.reaction.label) }} · {{ tally.count }}
          </p>
          <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
            {{ tally.users.map(user => user.name).filter(Boolean).join(', ') }}
          </p>
        </HoverCardContent>
      </HoverCard>
    </span>
  </TransitionGroup>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { REACTIONS, reactionFor } from '@/Components/Discussions/reactions';
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/Components/ui/hover-card';
import type { CommentReactionTally } from '@/Types/discussions';

const props = defineProps<{ reactions: CommentReactionTally[] }>();

defineEmits<{ toggle: [emoji: string] }>();

// A fixed order, so a chip never jumps when someone else reacts; unknown values are skipped.
const known = computed(() => props.reactions
  .flatMap((tally) => {
    const reaction = reactionFor(tally.emoji);
    return reaction ? [{ ...tally, reaction }] : [];
  })
  .sort((a, b) => REACTIONS.indexOf(a.reaction) - REACTIONS.indexOf(b.reaction)));
</script>
