<template>
  <Dialog :open="true" @update:open="onOpenChange">
    <DialogContent class="max-w-3xl" @interact-outside="onDialogInteractOutside">
      <DialogHeader>
        <DialogTitle class="flex flex-wrap items-center gap-2">
          <FileText class="h-4 w-4" />
          {{ document.title }}
        </DialogTitle>
      </DialogHeader>

      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
          <div v-if="doc.participants.value.length" class="flex -space-x-2">
            <UserAvatar
              v-for="participant in doc.participants.value"
              :key="participant.id"
              :user="(participant as any)"
              :size="24"
              class="ring-2 ring-white dark:ring-zinc-900"
            />
          </div>
          <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ presenceLabel }}</span>
        </div>
        <SaveStatusChip :status="doc.saveStatus.value" />
      </div>

      <div v-if="doc.isHydrating.value" class="space-y-2 rounded-lg border border-zinc-200 dark:border-zinc-800 px-3 py-4">
        <div class="h-3 w-3/4 animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
        <div class="h-3 w-full animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
        <div class="h-3 w-2/3 animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
      </div>

      <div v-else class="max-h-[60vh] min-h-64 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950/40">
        <CollaborativeDocEditor
          :doc="doc.doc"
          :awareness="doc.awareness"
          :user-name="currentUser.name"
          :user-color="doc.currentUserColor"
          :mention-users="mentionUsers"
          @html-change="doc.setHtml"
        />
      </div>

      <p class="text-[11px] leading-relaxed text-zinc-400">
        {{ $t('Pažymėkite tekstą formatavimui, „/" atveria blokų meniu, „@" pamini atstovą.') }}
      </p>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { FileText } from 'lucide-vue-next';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import CollaborativeDocEditor from '@/Components/CollaborativeDocs/CollaborativeDocEditor.vue';
import SaveStatusChip from '@/Components/CollaborativeDocs/SaveStatusChip.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import type { NotesMentionUser } from '@/Composables/useCollaborativeDocument';
import { useWorkspaceDocument } from '@/Composables/useWorkspaceDocument';

const props = withDefaults(defineProps<{
  workspaceId: string;
  document: { id: string; title: string };
  currentUser: { id: string | number; name: string };
  mentionUsers?: NotesMentionUser[];
}>(), {
  mentionUsers: () => [],
});

const emit = defineEmits<{ close: [] }>();

// The composable lives for exactly as long as this dialog is mounted; unmount
// flushes unsaved changes and leaves the presence channel.
const doc = useWorkspaceDocument(props.workspaceId, props.document.id, props.currentUser);

const onOpenChange = (open: boolean) => {
  if (!open) {
    emit('close');
  }
};

// Keep the dialog open when the click/focus lands on a Tiptap menu (mention /
// slash dropdowns are portalled to <body>, i.e. "outside").
const onDialogInteractOutside = (event: { detail?: { originalEvent?: Event }; preventDefault: () => void }) => {
  const target = event.detail?.originalEvent?.target as HTMLElement | null;
  if (target?.closest?.('[data-collab-doc-menu]')) {
    event.preventDefault();
  }
};

const presenceLabel = computed(() => {
  const count = doc.participants.value.length;
  if (count <= 1) {
    return $t('Tik jūs');
  }
  return $t(':count žiūri', { count });
});
</script>
