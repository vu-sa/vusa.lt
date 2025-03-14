<template>
  <div class="mt-4 flex w-full flex-col gap-4">
    <FadeTransition v-if="showHistory">
      <div class="ml-auto flex items-center gap-2">
        <NButtonGroup size="tiny">
          <NButton :disabled="history?.length < 2" @click="undo()">
            <template #icon>
              <IFluentArrowUndo24Filled />
            </template>
          </NButton>
          <NButton @click="redo()">
            <template #icon>
              <IFluentArrowRedo24Filled />
            </template>
          </NButton>
        </NButtonGroup>
        <p class="text-xs leading-5 text-zinc-400">
          Grąžinti turinio blokų tvarką
        </p>
      </div>
    </FadeTransition>
    <TransitionGroup ref="el" tag="div">
      <div v-for="content, index in contents" :key="content?.id ?? content?.key"
        class="relative grid w-full grid-cols-[24px__1fr] gap-4 border border-zinc-300 p-3 shadow-xs first:rounded-t-lg last:rounded-b-lg dark:border-zinc-700/40 dark:bg-zinc-800/5">
        <NButton class="handle" style="height: 100%;" quaternary size="small">
          <template #icon>
            <IFluentReOrderDotsVertical24Regular />
          </template>
        </NButton>
        <RichContentEditorListElement :id="content?.id" :is-expanded="content?.expanded ?? true"
          :can-delete="contents?.length > 1" :icon="contentTypes.find((type) => type.value === content?.type)?.icon"
          :title="contentTypes.find((type) => type.value === content?.type)?.label"
          @up="moveArrayElement(contents, index, index - 1)" @down="moveArrayElement(contents, index, index + 1)"
          @expand="content.expanded = !content?.expanded" @remove="handleElementRemove(index)">
          <!-- Text -->
          <OriginalTipTap v-if="content?.type === 'tiptap'" v-show="content.expanded ?? true"
            v-model="content.json_content" />
          <!-- Collapse -->
          <NDynamicInput v-else-if="content?.type === 'shadcn-accordion'" v-show="content.expanded"
            v-model:value="content.json_content" @create="onCreate">
            <template #create-button-default>
              Sukurti
            </template>
            <template #default="{ value }">
              <div
                class="flex w-full flex-col gap-3 rounded-lg border border-zinc-200/60 bg-zinc-50/30 p-4 dark:border-zinc-800/50 dark:bg-zinc-800/20">
                <NFormItem label="Pavadinimas" :show-feedback="false">
                  <NInput v-model:value="value.label" type="text" placeholder="Įrašyti pavadinimą..." />
                </NFormItem>
                <OriginalTipTap v-model="value.content" />
              </div>
            </template>
          </NDynamicInput>
          <!-- Card -->
          <div v-if="content?.type === 'shadcn-card'" v-show="content.expanded" class="flex flex-col gap-4">
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
          <!-- Image Grid -->
          <div v-else-if="content?.type === 'image-grid'" v-show="content.expanded" class="mt-4 flex flex-col gap-4">
            <NDynamicInput v-model:value="content.json_content" @create="onCreateGridImage">
              <template #create-button-default>
                Sukurti
              </template>
              <template #default="{ value }">
                <div class="flex w-full gap-4">
                  <NFormItem class="w-48" label="Nuotraukos plotis" :show-feedback="false">
                    <NSelect v-model:value="value.colspan" type="text" placeholder="Layout"
                      :options="imageGridOptions" />
                  </NFormItem>
                  <NFormItem class="grow" label="Nuotrauka" :show-feedback="false">
                    <div>
                      <TiptapImageButton v-if="!value.image" size="medium" class="grow" @submit="value.image = $event">
                        Pasirinkti paveikslėlį
                      </TiptapImageButton>
                      <img v-else :src="value.image" class="aspect-video h-24 rounded-lg object-cover">
                    </div>
                  </NFormItem>
                </div>
              </template>
            </NDynamicInput>
          </div>
          <!-- Hero -->
          <HeroForm v-else-if="content?.type === 'hero'" v-show="content.expanded" v-model:options="content.options"
            v-model:json_content="content.json_content" class="mt-4 flex flex-col gap-4" />
          <!-- Spotify Embed -->
          <div v-else-if="content?.type === 'spotify-embed'" v-show="content.expanded" class="mt-4 flex flex-col gap-4">
            <NFormItem label="Spotify URL" :show-feedback="false">
              <NInput v-model:value="content.json_content.url" type="text" />
            </NFormItem>
          </div>
          <!-- Flow Graph -->
          <div v-else-if="content?.type === 'flow-graph'" v-show="content.expanded" class="mt-4 flex flex-col gap-4">
            <NFormItem label="Flow Graph" :show-feedback="false">
              <NSelect v-model:value="content.json_content.preset" :options="[
                { 'label': 'VusaStructure', 'value': 'VusaStructure' }
              ]" />
            </NFormItem>
          </div>
          <!-- News -->
          <div v-else-if="content?.type === 'news'" v-show="content.expanded" class="mt-4 flex flex-col gap-4">
            <NFormItem label="Pavadinimas" :show-feedback="false">
              <NInput v-model:value="content.json_content.title" type="text" />
            </NFormItem>
          </div>
          <!-- Calendar -->
          <div v-else-if="content?.type === 'calendar'" v-show="content.expanded" class="mt-4 flex flex-col gap-4">
            <NFormItem label="Pavadinimas" :show-feedback="false">
              <NInput v-model:value="content.json_content.title" type="text" />
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
import { moveArrayElement, useSortable } from "@vueuse/integrations/useSortable";
import { nextTick, ref } from 'vue';
import { useManualRefHistory } from '@vueuse/core';

import AppsListDetail24Regular from '~icons/fluent/apps-list-detail24-regular';
import CalendarDay24Regular from '~icons/fluent/calendar-day24-regular';
import ImageMultiple24Regular from '~icons/fluent/image-multiple24-regular';
import TextCaseUppercase20Filled from '~icons/fluent/text-case-uppercase20-filled';

import CardModal from './Modals/CardModal.vue';
import FadeTransition from './Transitions/FadeTransition.vue';
import InfoPopover from './Buttons/InfoPopover.vue';
import OriginalTipTap from './TipTap/OriginalTipTap.vue';
import RichContentEditorListElement from './RichContentEditorListElement.vue';
import TiptapImageButton from './TipTap/TiptapImageButton.vue';
import HeroForm from "./RichContent/RCHeroSection/HeroForm.vue";

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

function onCreateGridImage() {
  return {
    colspan: "col-span-2",
    image: "",
  };
}

function handleElementCreate(selectedContent) {
  commit();

  const jsonContentTemplate = selectedContent === "shadcn-accordion" ? [] : {};

  contents.value?.push({ json_content: jsonContentTemplate, type: selectedContent, options: { 'is_active': true }, key: Math.random().toString(36).substring(7), expanded: true })
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

const imageGridOptions = [
  {
    label: '1/1',
    value: 'col-span-full',
  }, {
    label: '1/3',
    value: 'col-span-2',
  }, {
    label: '2/3',
    value: 'col-span-4',
  }, {
    label: '1/2',
    value: 'col-span-3',
  }
]

const contentTypes = [
  {
    value: "tiptap",
    label: "Tekstas",
    icon: TextCaseUppercase20Filled,
  },
  {
    value: "shadcn-accordion",
    label: "Išsiskleidžiantis sąrašas",
    icon: AppsListDetail24Regular,
  },
  {
    value: "shadcn-card",
    label: "Kortelė",
    icon: CalendarDay24Regular,
  },
  {
    value: "image-grid",
    label: "Nuotraukų tinklelis",
    icon: ImageMultiple24Regular,
  },
  {
    value: "hero",
    label: "Hero",
    icon: ImageMultiple24Regular,
  },
  {
    value: "news",
    label: "Naujienos",
    icon: ImageMultiple24Regular,
  },
  {
    value: "calendar",
    label: "Kalendorius",
    icon: ImageMultiple24Regular,
  },
  {
    value: "spotify-embed",
    label: "Spotify",
    icon: ImageMultiple24Regular,
  },
  {
    value: "flow-graph",
    label: "Flow Graph",
    icon: ImageMultiple24Regular,
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
