<template>
  <div class="flex flex-wrap items-center gap-1">
    <Tooltip v-for="tally in reactions" :key="tally.emoji">
      <TooltipTrigger as-child>
        <button
          type="button"
          :class="[
            'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs transition-colors',
            tally.reacted_by_me
              ? 'border-vusa-red/40 bg-vusa-red/10 text-vusa-red'
              : 'border-zinc-200 bg-zinc-50 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
          ]"
          @click="$emit('toggle', tally.emoji)"
        >
          <span>{{ tally.emoji }}</span>
          <span class="tabular-nums">{{ tally.count }}</span>
        </button>
      </TooltipTrigger>
      <TooltipContent>
        {{ tally.users.map(u => u.name).filter(Boolean).join(', ') }}
      </TooltipContent>
    </Tooltip>

    <Popover>
      <PopoverTrigger as-child>
        <button
          type="button"
          class="inline-flex h-6 w-6 items-center justify-center rounded-full text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800"
          :title="$t('Pridėti reakciją')"
        >
          <SmilePlus class="h-3.5 w-3.5" />
        </button>
      </PopoverTrigger>
      <PopoverContent class="w-auto p-1" align="start">
        <div class="flex items-center gap-0.5">
          <button
            v-for="emoji in allowedReactions"
            :key="emoji"
            type="button"
            class="flex h-8 w-8 items-center justify-center rounded-md text-base transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800"
            @click="$emit('toggle', emoji)"
          >
            {{ emoji }}
          </button>
        </div>
      </PopoverContent>
    </Popover>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { SmilePlus } from 'lucide-vue-next';

import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';
import type { CommentReactionTally } from '@/Types/discussions';

defineProps<{
  reactions: CommentReactionTally[];
}>();

defineEmits<{ toggle: [emoji: string] }>();

// Mirrors Comment::ALLOWED_REACTIONS on the backend.
const allowedReactions = ['👍', '❤️', '✅', '🎉', '👀', '🙏'];
</script>
