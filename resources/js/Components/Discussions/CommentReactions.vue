<template>
  <div class="flex flex-wrap items-center gap-1">
    <Tooltip v-for="tally in reactions" :key="tally.emoji">
      <TooltipTrigger as-child>
        <button
          type="button"
          :class="[
            'inline-flex items-center gap-1 border px-2 py-0.5 text-xs transition-colors pointer-coarse:min-h-11',
            tally.reacted_by_me
              ? 'border-brand/40 bg-brand/10 text-brand'
              : 'border-border bg-background text-muted-foreground hover:bg-accent hover:text-foreground',
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
          class="inline-flex size-7 items-center justify-center text-muted-foreground transition-colors hover:bg-accent hover:text-foreground pointer-coarse:size-11"
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
            class="flex size-9 items-center justify-center text-base transition-colors hover:bg-accent pointer-coarse:size-11"
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
