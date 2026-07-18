<template>
  <aside class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/40">
    <div class="flex items-center justify-between gap-2 border-b border-zinc-200 dark:border-zinc-800 px-4 py-3">
      <div class="flex items-center gap-2">
        <Users class="h-4 w-4 text-zinc-500 dark:text-zinc-400" />
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
          {{ $t('Atstovų pastabos') }}
        </h3>
      </div>
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 dark:bg-amber-900/20 px-2 py-0.5 text-[11px] font-semibold text-amber-600 dark:text-amber-400 ring-1 ring-amber-200/70 dark:ring-amber-900/50">
          <Lock class="h-3 w-3" />
          {{ $t('PRIVATU') }}
        </span>
        <SpotlightPopover
          :title="$t('Bendros pastabos realiu laiku')"
          :description="$t('Rašykite pastabas kartu su kitais atstovais — pakeitimai matomi iškart. Spauskite, kad atvertumėte didesnį langą.')"
          position="left"
          :show-badge="spotlight.isVisible.value"
          :is-dismissed="spotlight.isDismissed.value"
          @dismiss="spotlight.dismiss"
        >
          <Button variant="ghost" size="icon" class="h-7 w-7" :title="$t('Atverti didesnį langą')" @click="expand">
            <Maximize2 class="h-4 w-4" />
          </Button>
        </SpotlightPopover>
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
            class="ring-2 ring-white dark:ring-zinc-900"
          />
          <span
            v-if="extraParticipants > 0"
            class="flex h-[22px] w-[22px] items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700 text-[10px] font-semibold text-zinc-600 dark:text-zinc-200 ring-2 ring-white dark:ring-zinc-900"
          >
            +{{ extraParticipants }}
          </span>
        </div>
        <span class="text-[11px] text-zinc-500 dark:text-zinc-400">
          {{ presenceLabel }}
        </span>
      </div>
      <SaveStatusChip :status="notes.saveStatus.value" />
    </div>

    <!-- Body -->
    <div class="px-2 pb-3">
      <div v-if="notes.isHydrating.value" class="space-y-2 px-2 py-3">
        <div class="h-3 w-3/4 animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
        <div class="h-3 w-full animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
        <div class="h-3 w-2/3 animate-pulse rounded bg-zinc-200 dark:bg-zinc-800" />
      </div>

      <!-- While the dialog holds the live editor, the sidebar shows a snapshot. -->
      <div v-else-if="expanded" class="px-2">
        <div
          v-if="notes.notesHtml.value"
          class="typography max-h-64 overflow-y-auto text-sm"
          v-html="notes.notesHtml.value"
        />
        <p v-else class="py-3 text-xs text-zinc-400">
          {{ $t('Nėra pastabų.') }}
        </p>
        <p class="mt-2 text-[11px] italic text-zinc-400">
          {{ $t('Redaguojama atskirame lange…') }}
        </p>
      </div>

      <div v-else class="max-h-72 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950/40">
        <CollaborativeDocEditor
          :doc="notes.doc"
          :awareness="notes.awareness"
          :user-name="currentUser.name"
          :user-color="notes.currentUserColor"
          :mention-users="notes.representatives.value"
          @html-change="notes.setHtml"
        />
      </div>

      <p v-if="!notes.isHydrating.value && !expanded" class="px-2 pt-2 text-[11px] leading-relaxed text-zinc-400">
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
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 dark:bg-amber-900/20 px-2 py-0.5 text-[11px] font-semibold text-amber-600 dark:text-amber-400">
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
                class="ring-2 ring-white dark:ring-zinc-900"
              />
            </div>
            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ presenceLabel }}</span>
          </div>
          <SaveStatusChip :status="notes.saveStatus.value" />
        </div>

        <div class="max-h-[60vh] min-h-64 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950/40">
          <CollaborativeDocEditor
            v-if="expanded"
            :doc="notes.doc"
            :awareness="notes.awareness"
            :user-name="currentUser.name"
            :user-color="notes.currentUserColor"
            :mention-users="notes.representatives.value"
            @html-change="notes.setHtml"
          />
        </div>

        <p class="text-[11px] leading-relaxed text-zinc-400">
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

import CollaborativeDocEditor from '@/Components/CollaborativeDocs/CollaborativeDocEditor.vue';
import SaveStatusChip from '@/Components/CollaborativeDocs/SaveStatusChip.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { useAgendaItemNotes } from '@/Composables/useAgendaItemNotes';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

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
const spotlight = useFeatureSpotlight('agenda-notes-v1');

const expand = () => {
  expanded.value = true;
  if (spotlight.isVisible.value) {
    spotlight.dismiss();
  }
};

// Keep the expanded dialog open when the click/focus lands on a Tiptap menu
// (e.g. the @mention dropdown, which is portalled to <body>, i.e. "outside").
const onDialogInteractOutside = (event: { detail?: { originalEvent?: Event }; preventDefault: () => void }) => {
  const target = event.detail?.originalEvent?.target as HTMLElement | null;
  if (target?.closest?.('[data-collab-doc-menu]')) {
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
