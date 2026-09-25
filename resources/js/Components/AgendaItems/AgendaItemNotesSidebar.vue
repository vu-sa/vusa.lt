<template>
  <aside data-slot="agenda-item-notes" class="border border-border bg-card">
    <div class="flex items-center justify-between gap-2 border-b border-border px-4 py-3">
      <div class="flex min-w-0 items-center gap-2">
        <Users class="size-4 shrink-0 text-muted-foreground" />
        <h3 class="truncate whitespace-nowrap text-sm font-semibold text-foreground">
          {{ $t('Atstovų pastabos') }}
        </h3>
      </div>
      <div class="flex shrink-0 items-center gap-1.5">
        <span class="inline-flex items-center gap-1 border border-status-attention-border bg-status-attention-surface px-1.5 py-0.5 text-xs font-semibold text-status-attention">
          <Lock class="h-3 w-3" />
          {{ $t('PRIVATU') }}
        </span>
        <Button variant="ghost" size="icon" class="h-7 w-7" :title="$t('Atverti didesnį langą')" @click="expanded = true">
          <Maximize2 class="h-4 w-4" />
        </Button>
      </div>
    </div>

    <!-- Presence + save status -->
    <div class="flex items-center justify-between gap-2 px-4 py-2">
      <div class="flex items-center gap-2">
        <div v-if="notes.participants.value.length" class="flex -space-x-2">
          <UserAvatar
            v-for="participant in visibleParticipants"
            :key="participant.id"
            :user="(participant as any)"
            :size="22"
            class="ring-2 ring-card"
          />
          <span
            v-if="extraParticipants > 0"
            :class="OVERFLOW_AVATAR_CLASS"
          >
            +{{ extraParticipants }}
          </span>
        </div>
        <span class="text-xs text-muted-foreground">
          {{ presenceLabel }}
        </span>
      </div>
      <SaveStatusChip :status="notes.saveStatus.value" />
    </div>

    <!-- Body -->
    <div class="px-2 pb-3">
      <div v-if="notes.isHydrating.value" class="space-y-2 px-2 py-3">
        <div class="h-3 w-3/4 animate-pulse bg-muted" />
        <div class="h-3 w-full animate-pulse bg-muted" />
        <div class="h-3 w-2/3 animate-pulse bg-muted" />
      </div>

      <!-- While the dialog holds the live editor, the sidebar shows a snapshot. -->
      <div v-else-if="expanded" class="px-2">
        <div
          v-if="notes.notesHtml.value"
          class="typography max-h-64 overflow-y-auto text-sm"
          v-html="notes.notesHtml.value"
        />
        <p v-else class="py-3 text-xs text-muted-foreground">
          {{ $t('Nėra pastabų.') }}
        </p>
        <p class="mt-2 text-xs italic text-muted-foreground">
          {{ $t('Redaguojama atskirame lange…') }}
        </p>
      </div>

      <div v-else class="max-h-72 overflow-y-auto border border-border bg-background">
        <AgendaItemNotesEditor
          :doc="notes.doc"
          :awareness="notes.awareness"
          :user-name="currentUser.name"
          :user-color="notes.currentUserColor"
          :representatives="notes.representatives.value"
          @html-change="notes.setHtml"
        />
      </div>

      <p v-if="!notes.isHydrating.value && !expanded" class="px-2 pt-2 text-xs leading-relaxed text-muted-foreground">
        <span class="font-semibold">{{ $t('Eksperimentinė funkcija') }}.</span>
        {{ $t('Pažymėkite tekstą formatavimui, „/" atveria blokų meniu, „@" pamini atstovą.') }}
      </p>
    </div>

    <!-- Expanded dialog -->
    <Dialog v-model:open="expanded">
      <DialogContent class="max-w-3xl" @interact-outside="onDialogInteractOutside">
        <DialogHeader>
          <DialogTitle class="flex flex-wrap items-center gap-2">
            <Users class="h-4 w-4" />
            {{ $t('Atstovų pastabos') }}
            <span class="inline-flex items-center gap-1 border border-status-attention-border bg-status-attention-surface px-1.5 py-0.5 text-xs font-semibold text-status-attention">
              <Lock class="h-3 w-3" />
              {{ $t('PRIVATU') }}
            </span>
          </DialogTitle>
        </DialogHeader>

        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <div v-if="notes.participants.value.length" class="flex -space-x-2">
              <UserAvatar
                v-for="participant in notes.participants.value"
                :key="participant.id"
                :user="(participant as any)"
                :size="24"
                class="ring-2 ring-card"
              />
            </div>
            <span class="text-xs text-muted-foreground">{{ presenceLabel }}</span>
          </div>
          <SaveStatusChip :status="notes.saveStatus.value" />
        </div>

        <div class="max-h-[60vh] min-h-64 overflow-y-auto border border-border bg-background">
          <AgendaItemNotesEditor
            v-if="expanded"
            :doc="notes.doc"
            :awareness="notes.awareness"
            :user-name="currentUser.name"
            :user-color="notes.currentUserColor"
            :representatives="notes.representatives.value"
            @html-change="notes.setHtml"
          />
        </div>

        <p class="text-xs leading-relaxed text-muted-foreground">
          <span class="font-semibold">{{ $t('Eksperimentinė funkcija') }}.</span>
          {{ $t('Pažymėkite tekstą formatavimui, „/" atveria blokų meniu, „@" pamini atstovą.') }}
        </p>
      </DialogContent>
    </Dialog>
  </aside>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Lock, Maximize2, Users } from 'lucide-vue-next';

import AgendaItemNotesEditor from '@/Components/AgendaItems/AgendaItemNotesEditor.vue';
import SaveStatusChip from '@/Components/AgendaItems/NotesSaveStatusChip.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { useAgendaItemNotes } from '@/Composables/useAgendaItemNotes';

// eslint-disable-next-line admin-redesign/no-legacy-utility -- circular: it sits in a row of avatars
const OVERFLOW_AVATAR_CLASS = 'flex size-6 items-center justify-center rounded-full bg-muted text-xs font-semibold text-muted-foreground ring-2 ring-card';

const props = defineProps<{
  agendaItemId: string;
}>();

const page = usePage();
const currentUser = computed(() => {
  const user = (page.props.auth as { user?: { id: string | number; name: string } } | undefined)?.user;
  return { id: user?.id ?? 'anonymous', name: user?.name ?? $t('Atstovas') };
});

const notes = useAgendaItemNotes(props.agendaItemId, currentUser.value);

const expanded = ref(false);

// Keep the expanded dialog open when the click/focus lands on a Tiptap menu
// (e.g. the @mention dropdown, which is portalled to <body>, i.e. "outside").
const onDialogInteractOutside = (event: { detail?: { originalEvent?: Event }; preventDefault: () => void }) => {
  const target = event.detail?.originalEvent?.target as HTMLElement | null;
  if (target?.closest?.('[data-agenda-notes-menu]')) {
    event.preventDefault();
  }
};

const visibleParticipants = computed(() => notes.participants.value.slice(0, 3));
const extraParticipants = computed(() => Math.max(0, notes.participants.value.length - 3));

const presenceLabel = computed(() => {
  const count = notes.participants.value.length;
  if (count <= 1) {
    return $t('Tik jūs');
  }
  return $t(':count žiūri', { count });
});
</script>
