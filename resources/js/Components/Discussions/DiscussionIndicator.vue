<template>
  <Sheet v-if="commentableType && commentableId">
    <SheetTrigger as-child>
      <button type="button" :class="triggerClass" :title="$t('Diskusija')">
        <MessageSquare class="h-3.5 w-3.5" />
        <span v-if="count" class="tabular-nums">{{ count }}</span>
        <span
          v-if="hasNotes"
          class="h-1.5 w-1.5 rounded-full bg-amber-400"
          :title="$t('Yra pastabų')"
        />
      </button>
    </SheetTrigger>
    <SheetContent class="w-full overflow-y-auto sm:max-w-lg">
      <SheetHeader>
        <SheetTitle>{{ title || $t('Diskusija') }}</SheetTitle>
      </SheetHeader>
      <div class="mt-4">
        <DiscussionPanel :commentable-type="commentableType" :commentable-id="commentableId" />
      </div>
    </SheetContent>
  </Sheet>

  <button v-else type="button" :class="triggerClass" :title="$t('Diskusija')" @click="$emit('open')">
    <MessageSquare class="h-3.5 w-3.5" />
    <span v-if="count" class="tabular-nums">{{ count }}</span>
    <span v-if="hasNotes" class="h-1.5 w-1.5 rounded-full bg-amber-400" :title="$t('Yra pastabų')" />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { MessageSquare } from 'lucide-vue-next';

import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/Components/ui/sheet';

const props = withDefaults(defineProps<{
  count?: number;
  hasNotes?: boolean;
  commentableType?: string;
  commentableId?: string;
  title?: string;
}>(), {
  count: 0,
  hasNotes: false,
});

defineEmits<{ open: [] }>();

const triggerClass = computed(() => [
  'inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs transition-colors',
  props.count || props.hasNotes
    ? 'text-muted-foreground hover:bg-zinc-100 hover:text-foreground dark:hover:bg-zinc-800'
    : 'text-muted-foreground/50 hover:bg-zinc-100 hover:text-muted-foreground dark:hover:bg-zinc-800',
]);
</script>
