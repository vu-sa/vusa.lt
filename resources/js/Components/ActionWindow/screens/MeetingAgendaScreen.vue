<template>
  <ActionWindowScreen
    :title="$t('action_window.meeting.agenda.title')"
    :subtitle="$t('action_window.meeting.agenda.subtitle')"
  >
    <ActionChoiceList v-if="!editing">
      <ActionChoiceButton
        :title="$t('action_window.meeting.agenda.add')"
        :description="$t('action_window.meeting.agenda.add_description')"
        :icon="ListPlus"
        :gradient="AGENDA_TINT"
        @click="startEditing"
      />
      <ActionChoiceButton
        :title="$t('action_window.meeting.agenda.skip')"
        :description="$t('action_window.meeting.agenda.skip_description')"
        :icon="SkipForward"
        @click="skip"
      />
    </ActionChoiceList>

    <AgendaItemsEditor v-else v-model="titles" />

    <template v-if="editing" #footer>
      <div class="flex items-center gap-2">
        <Button variant="ghost" size="lg" @click="editing = false">
          <ChevronLeft class="mr-1 size-4" />
          {{ $t('action_window.common.back') }}
        </Button>
        <Button class="flex-1" size="lg" @click="submit">
          {{ $t('action_window.common.continue') }}
        </Button>
      </div>
    </template>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { ChevronLeft, ListPlus, SkipForward } from 'lucide-vue-next';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';
import AgendaItemsEditor from '../AgendaItemsEditor.vue';

import { useActionWindow } from '@/Composables/useActionWindow';
import { Button } from '@/Components/ui/button';
import type { AgendaItemFormData } from '@/Composables/useMeetingCreation';

const AGENDA_TINT = 'from-sky-500/15 to-indigo-500/15 dark:from-sky-400/12 dark:to-indigo-400/12';

const { draft, advance, setAgendaItems } = useActionWindow();

const titles = ref<string[]>(draft.agendaItems.length > 0
  ? draft.agendaItems.map(item => item.title)
  : ['']);

const editing = ref(draft.agendaItems.length > 0);

const startEditing = () => {
  editing.value = true;
};

const skip = () => {
  setAgendaItems([]);
  advance('meeting.review');
};

const submit = () => {
  // Blank lines are how the editor grows, not something the user meant to add.
  const items: AgendaItemFormData[] = titles.value
    .map(title => title.trim())
    .filter(title => title !== '')
    .map((title, index) => ({ title, description: '', order: index + 1 }));

  setAgendaItems(items);
  advance('meeting.review');
};
</script>
