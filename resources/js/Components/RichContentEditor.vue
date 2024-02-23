<template>
  <div ref="el" class="flex w-full flex-col gap-6">
    <div v-for="content, index in contents" :key="content.id"
      class="relative grid w-full grid-cols-[24px_1fr] gap-4 rounded-md border border-zinc-800 p-3 shadow-md dark:border-zinc-700/40 dark:bg-zinc-800/5">
      <div class="absolute -right-4 -top-4">
        <NButton :disabled="contents?.length < 2" circle type="error" size="small" @click="contents?.splice(index, 1)">
          <template #icon>
            <NIcon :component="Dismiss24Regular" />
          </template>
        </NButton>
      </div>
      <NButton class="handle" style="height: 100%;" quaternary size="small">
        <template #icon>
          <NIcon :component="ReOrderDotsVertical24Regular" />
        </template>
      </NButton>
      <!-- Text -->
      <section v-if="content.type === 'tiptap'">
        <h3 class="inline-flex items-center gap-2">
          <NIcon :component="TextCaseUppercase20Filled" />
          Tekstas
        </h3>
        <OriginalTipTap v-model="content.json_content" />
      </section>
      <!-- Accordion, Collapse -->
      <section v-else-if="content.type === 'naiveui-collapse'">
        <h3 class="inline-flex items-center gap-2">
          <NIcon :component="AppsListDetail24Regular" />
          Išsiskleidžiantis sąrašas
        </h3>
        <NDynamicInput v-model:value="content.json_content" @create="onCreate">
          <template #create-button-default>
            Sukurti
          </template>
          <template #default="{ value }">
            <div
              class="mt-2 flex w-full flex-col gap-6 rounded-lg border border-zinc-400/80 p-4 dark:border-zinc-800/50 dark:bg-zinc-800/20">
              <NFormItem label="Pavadinimas" :show-feedback="false">
                <NInput v-model:value="value.label" type="text" />
              </NFormItem>
              <OriginalTipTap v-model="value.content" />
            </div>
          </template>
        </NDynamicInput>
      </section>
    </div>
    <div class="my-2 flex max-w-64 gap-2">
      <NSelect v-model:value="selectedNewContent" :options="contentTypes" />
      <NButton type="primary" @click="contents?.push({ json_content: {}, type: selectedNewContent })">Sukurti
        naują
        turinio bloką
      </NButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { AppsListDetail24Regular, Dismiss24Regular, ReOrderDotsVertical24Regular, TextCaseUppercase20Filled } from '@vicons/fluent';
import { NButton, NDynamicInput, NFormItem, NIcon, NInput, NSelect } from 'naive-ui';
import { ref } from 'vue';
import { useSortable } from "@vueuse/integrations/useSortable";

import OriginalTipTap from './TipTap/OriginalTipTap.vue';

const contents = defineModel('contents');

const el = ref<HTMLElement | null>(null);

function onCreate() {
  return {
    label: "",
    content: {},
  };
}

const selectedNewContent = ref("tiptap");
const contentTypes = [
  {
    value: "tiptap",
    label: "Tekstas",
  },
  {
    value: "naiveui-collapse",
    label: "Akordeonas",
  }
];

useSortable(el, contents, { handle: ".handle", animation: 100 });

</script>
