<template>
  <div ref="el" class="mt-4 flex w-full flex-col gap-4">
    <FadeTransition v-if="showHistory">
      <div class="ml-auto flex items-center gap-2">
        <NButtonGroup size="tiny">
          <NButton :disabled="history?.length < 2" @click="undo()">
            <template #icon>
              <NIcon :component="ArrowUndo24Filled" />
            </template>
          </NButton>
          <NButton @click="redo()">
            <template #icon>
              <NIcon :component="ArrowRedo24Filled" />
            </template>
          </NButton>
        </NButtonGroup>
        <p class="text-xs leading-5 text-zinc-400">
          Grąžinti turinio blokų tvarką
        </p>
      </div>
    </FadeTransition>
    <TransitionGroup>
      <div v-for="content, index in contents " :key="content?.id ?? content?.key"
        class="relative grid w-full grid-cols-[24px_1fr] gap-4 rounded-md border border-zinc-300 p-3 shadow-sm dark:border-zinc-700/40 dark:bg-zinc-800/5">
        <NButton class="handle" style="height: 100%;" quaternary size="small">
          <template #icon>
            <NIcon :component="ReOrderDotsVertical24Regular" />
          </template>
        </NButton>
        <RichContentEditorListElement :id="content?.id" :is-expanded="content.expanded" :can-delete="contents?.length > 1"
          :icon="contentTypes.find((type) => type.value === content.type)?.icon"
          :title="contentTypes.find((type) => type.value === content.type)?.label"
          @expand="content.expanded = !content.expanded" @remove="handleElementRemove(index)">
          <!-- Text -->
          <OriginalTipTap v-if="content.type === 'tiptap'" v-show="content.expanded" v-model="content.json_content" />
          <!-- Collapse -->
          <NDynamicInput v-else-if="content.type === 'naiveui-collapse'" v-show="content.expanded"
            v-model:value="content.json_content" @create="onCreate">
            <template #create-button-default>
              Sukurti
            </template>
            <template #default="{ value }">
              <div
                class="mt-2 flex w-full flex-col gap-6 rounded-lg border border-zinc-200/60 bg-zinc-50/30 p-4 dark:border-zinc-800/50 dark:bg-zinc-800/20">
                <NFormItem label="Pavadinimas" :show-feedback="false">
                  <NInput v-model:value="value.label" type="text" />
                </NFormItem>
                <OriginalTipTap v-model="value.content" />
              </div>
            </template>
          </NDynamicInput>
          <!-- Card -->
          <div v-if="content.type === 'naiveui-card'" v-show="content.expanded" class="flex flex-col gap-4">
            <div class="grid grid-cols-3 items-center gap-7" label="Pavadinimas" :show-feedback="false">
              <NFormItem label="Variantas" :show-feedback="false">
                <NSelect v-model:value="content.options.variant" :options="[
                  { 'label': 'Outlined', 'value': 'outline' }, { 'label': 'Soft', 'value': 'soft' }]
                  " />
              </NFormItem>
              <NFormItem label="Spalva" :show-feedback="false">
                <NSelect v-model:value="content.options.color" :options="[{
                  'label': 'Pilka',
                  'value': 'zinc'
                }, {
                  'label': 'Raudona',
                  'value': 'red'
                }, {
                  'label': 'Geltona',
                  'value': 'yellow'
                }
                ]
                  " />
              </NFormItem>
              <div class="mt-4">
                <NCheckbox v-model:checked="content.options.isTitleColored" :checked-value="true"
                  :unchecked-value="false">
                  Ar spalvinti kortelės pavadinimą?
                </NCheckbox>
                <NCheckbox v-model:checked="content.options.showIcon" :checked-value="true" :unchecked-value="false">
                  Ar naudoti ikonėlę?
                  <InfoPopover>
                    <p>Ikona bus rodoma kairėje pusėje </p>
                    <ul>
                      <li> Raudona: šauktukas </li>
                      <li> Geltona: klaustukas </li>
                      <li> Pilka: informacija </li>
                    </ul>
                  </InfoPopover>
                </NCheckbox>
              </div>
            </div>
            <NFormItem label="Pavadinimas" :show-feedback="false">
              <NInput v-model:value="content.options.title" type="text" />
            </NFormItem>
            <NFormItem label="Turinys" :show-feedback="false">
            <OriginalTipTap v-model="content.json_content" />
            </NFormItem>
          </div>
        </RichContentEditorListElement>
      </div>
    </TransitionGroup>
    <div class="mb-6 mt-2 flex w-full gap-2">
      <NButton type="primary" @click="showSelection = true">
        Pridėti turinio bloką
      </NButton>
    </div>
    <CardModal v-model:show="showSelection" title="Pasirinkti turinio bloką" @close="showSelection = false">
      <div class="flex flex-wrap gap-2 text-sm">
        <NButton v-for="type in contentTypes" :key="type.value" @click="handleElementCreate(type.value)">
          <template #icon>
            <NIcon :component="type.icon" />
          </template>
          {{ type.label }}
        </NButton>
      </div>
    </CardModal>
  </div>
</template>

<script setup lang="ts">
import { AppsListDetail24Regular, ArrowRedo24Filled, ArrowUndo24Filled, CalendarDay24Regular, ReOrderDotsVertical24Regular, TextCaseUppercase20Filled } from '@vicons/fluent';
import { NButton, NButtonGroup, NCheckbox, NDynamicInput, NFormItem, NIcon, NInput, NModal, NSelect, NTag } from 'naive-ui';
import { moveArrayElement, useSortable } from "@vueuse/integrations/useSortable";
import { nextTick, ref } from 'vue';
import { useManualRefHistory } from '@vueuse/core';

import CardModal from './Modals/CardModal.vue';
import FadeTransition from './Transitions/FadeTransition.vue';
import InfoPopover from './Buttons/InfoPopover.vue';
import OriginalTipTap from './TipTap/OriginalTipTap.vue';
import RichContentEditorListElement from './RichContentEditorListElement.vue';
import FadeTransitionGroup from './Transitions/FadeTransitionGroup.vue';

const contents = defineModel('contents');

const { history, commit, undo, redo } = useManualRefHistory(contents, { clone: true, capacity: 30 });

const el = ref<HTMLElement | null>(null);
const showHistory = ref(false);
const showSelection = ref(false);

function onCreate() {
  return {
    label: "",
    content: {},
  };
}

function handleElementCreate(selectedContent) {
  commit();
  contents.value?.push({ json_content: {}, type: selectedContent, options: {}, key: Math.random().toString(36).substring(7), expanded: true })
  showHistory.value = true;

  nextTick(() => commit());

  showSelection.value = false;
}

function handleElementRemove(index: number) {
  commit();
  contents.value?.splice(index, 1);
  showHistory.value = true;

  nextTick(() => commit());
}

const contentTypes = [
  {
    value: "tiptap",
    label: "Tekstas",
    icon: TextCaseUppercase20Filled,
  },
  {
    value: "naiveui-collapse",
    label: "Išsiskleidžiantis sąrašas",
    icon: AppsListDetail24Regular,
  },
  {
    value: "naiveui-card",
    label: "Kortelė",
    icon: CalendarDay24Regular,
  },
];

useSortable(el, contents, {
  handle: ".handle", animation: 100,
  onUpdate: (e) => {
    commit();
    moveArrayElement(contents.value, e.oldIndex, e.newIndex)
    showHistory.value = true;
    nextTick(() => commit());
  }
});

</script>
