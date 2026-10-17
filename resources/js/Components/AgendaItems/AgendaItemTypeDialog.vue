<template>
  <!-- The action window's presentation without its flows: a bottom drawer on phones, a bounded dialog elsewhere. -->
  <component :is="isMobile ? Drawer : Dialog" :open @update:open="emit('update:open', $event)">
    <component
      :is="isMobile ? DrawerContent : DialogContent"
      :class="isMobile
        ? 'border-t border-border p-0 pb-[env(safe-area-inset-bottom)]'
        : 'flex max-h-[85vh] flex-col gap-0 overflow-hidden p-0 sm:max-w-[560px]'"
    >
      <VisuallyHidden>
        <component :is="isMobile ? DrawerTitle : DialogTitle">
          {{ $t('meetings.item.type') }}
        </component>
      </VisuallyHidden>
      <ActionWindowScreen :title="$t('meetings.item.type')" :subtitle="$t('meetings.item.type_question')">
        <ActionChoiceList>
          <ActionChoiceButton
            v-for="option in options"
            :key="option.value"
            :title="option.label"
            :description="option.description"
            :icon="option.icon"
            :selected="option.value === form.type"
            :show-chevron="false"
            :data-testid="`agenda-item-type-${option.value}`"
            @click="choose(option.value)"
          />
        </ActionChoiceList>
      </ActionWindowScreen>
    </component>
  </component>
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { VisuallyHidden } from 'reka-ui';

import { agendaItemTypeOptions, applyAgendaItemType, type AgendaItemType } from './agendaItemTypes';

import ActionChoiceButton from '@/Components/ActionWindow/ActionChoiceButton.vue';
import ActionChoiceList from '@/Components/ActionWindow/ActionChoiceList.vue';
import ActionWindowScreen from '@/Components/ActionWindow/ActionWindowScreen.vue';
import { Dialog, DialogContent, DialogTitle } from '@/Components/ui/dialog';
import { Drawer, DrawerContent, DrawerTitle } from '@/Components/ui/drawer';
import type { AgendaItemFormData } from '@/Composables/useAgendaItemAutosave';
import { useIsMobile } from '@/Composables/useIsMobile';

const props = defineProps<{
  open: boolean;
  /** The live, autosaving form: a choice is the whole answer, so it saves and closes. */
  form: InertiaForm<AgendaItemFormData>;
}>();

const emit = defineEmits<{
  'update:open': [value: boolean];
}>();

const isMobile = useIsMobile();
const options = agendaItemTypeOptions();

function choose(value: AgendaItemType): void {
  applyAgendaItemType(props.form, value);
  emit('update:open', false);
}
</script>
